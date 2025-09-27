<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../assets/plugins/axios.js';
import { useNotificationStore } from '@/stores/notification';

const router = useRouter();
const notificationStore = useNotificationStore();

// --- ESTADOS DA PÁGINA ---
const projetos = ref([]);
const carregando = ref(true);
const erro = ref(null);
const filtroBusca = ref('');
const viewMode = ref('grid');
let userId = null;

// --- OBTÉM DADOS DO USUÁRIO LOGADO ---
const userDataString = sessionStorage.getItem('user_data');
if (userDataString) {
  const userData = JSON.parse(userDataString);
  userId = userData.user.id_usuario;
}

// --- LÓGICA DE BUSCA DE DADOS ---
onMounted(async () => {
  carregando.value = true;
  erro.value = null;

  if (!userId) {
    erro.value = "Não foi possível identificar o usuário. Por favor, faça o login novamente.";
    notificationStore.showError(erro.value);
    carregando.value = false;
    return;
  }

  try {
    // 1. Busca a lista inicial de projetos orientados
    const url = `/usuarios/${userId}/projetos/avaliacao`;
    const { data: projetosIniciais } = await api.get(url);

    if (!Array.isArray(projetosIniciais) || projetosIniciais.length === 0) {
        projetos.value = [];
        return; // Encerra a execução se não houver projetos
    }

    // 2. Cria uma promessa para buscar os membros de cada projeto
    const memberFetchPromises = projetosIniciais.map(projeto =>
        api.get(`/membros_projeto/${projeto.id_projeto}`).catch(err => {
            console.warn(`Não foi possível buscar membros para o projeto ${projeto.id_projeto}:`, err);
            return { data: [] }; // Retorna um array vazio em caso de erro para não quebrar o Promise.all
        })
    );

    // 3. Executa todas as buscas de membros em paralelo
    const membrosResponses = await Promise.all(memberFetchPromises);

    // 4. Combina os dados dos projetos com os dados de seus membros
    const projetosComMembros = projetosIniciais.map((projeto, index) => {
        const membrosData = membrosResponses[index].data;
        // Adiciona a lista de membros e a contagem ao objeto do projeto
        return {
            ...projeto,
            membros_equipe: Array.isArray(membrosData) ? membrosData : [],
        };
    });

    // 5. Transforma os dados combinados para o formato que a view utiliza
    projetos.value = projetosComMembros.map(transformarProjeto);

  } catch (err) {
    console.error("Erro ao buscar projetos para avaliação:", err);
    erro.value = "Não foi possível carregar seus projetos. Tente novamente mais tarde.";
    notificationStore.showError(erro.value);
  } finally {
    carregando.value = false;
  }
});

// --- FUNÇÕES DE TRANSFORMAÇÃO E VISUALIZAÇÃO ---

const transformarProjeto = (apiProjeto) => {
  return {
    id: apiProjeto.id_projeto,
    titulo: apiProjeto.titulo,
    // CORREÇÃO: Acessa o nome do evento através do objeto 'eventos' (no plural)
    evento: apiProjeto.eventos?.nome || 'Evento não definido',
    // A contagem de membros agora vem do array de membros que foi buscado dinamicamente
    membrosCount: apiProjeto.membros_equipe?.length ?? 0,
    status: getStatusInfo(apiProjeto.id_situacao),
  };
};

const getStatusInfo = (idSituacao) => {
    const statusMap = {
        2: { text: 'Ativo', color: 'green-darken-2' },
        3: { text: 'Em Avaliação', color: 'orange-darken-2' },
        4: { text: 'Concluído', color: 'blue-darken-2' },
        5: { text: 'Cancelado', color: 'red-darken-2' },
    };
    return statusMap[idSituacao] || { text: 'Aguardando', color: 'grey' };
}

const projetosFiltrados = computed(() => {
  if (!filtroBusca.value) {
    return projetos.value;
  }
  return projetos.value.filter(p =>
    p.titulo.toLowerCase().includes(filtroBusca.value.toLowerCase())
  );
});

// --- FUNÇÕES DE AÇÃO ---

const verDetalhesDoProjeto = (projeto) => {
  router.push(`/projetos/${projeto.id}`);
};
</script>

<template>
  <v-container fluid>
    <!-- CABEÇALHO -->
    <v-row class="mb-6" align="center">
      <v-col cols="12">
        <h1 class="text-h4 font-weight-bold text-teal-darken-4">
          Orientação de Projetos
        </h1>
        <p class="text-subtitle-1 text-grey-darken-2">
          Gerencie os projetos que você está orientando.
        </p>
      </v-col>
    </v-row>

    <!-- CARD DE FILTROS COM SELETOR DE VISUALIZAÇÃO -->
    <v-card class="mb-8 pa-4" variant="outlined">
      <v-row align="center" no-gutters>
        <v-col cols="12" md="8">
          <v-text-field
           v-model="filtroBusca"
            label="Buscar por título do projeto..."
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
            density="compact"
            hide-details
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="4" class="d-flex justify-start justify-md-end mt-4 mt-md-0">
          <v-btn-toggle v-model="viewMode" mandatory density="compact" variant="outlined">
            <v-btn value="grid" aria-label="Visualização em Grade">
              <v-icon>mdi-view-grid-outline</v-icon>
              <v-tooltip activator="parent" location="bottom">Grade</v-tooltip>
            </v-btn>
            <v-btn value="list" aria-label="Visualização em Lista">
              <v-icon>mdi-view-list-outline</v-icon>
              <v-tooltip activator="parent" location="bottom">Lista</v-tooltip>
            </v-btn>
          </v-btn-toggle>
        </v-col>
      </v-row>
    </v-card>

    <!-- ESTADO DE CARREGAMENTO -->
    <div v-if="carregando">
      <v-row>
        <v-col v-for="n in 4" :key="n" cols="12" md="6">
          <v-skeleton-loader type="list-item-two-line"></v-skeleton-loader>
        </v-col>
      </v-row>
    </div>

    <!-- ESTADO DE ERRO -->
    <v-alert v-else-if="erro" type="error" variant="tonal" border="start" prominent>
      {{ erro }}
    </v-alert>

    <!-- CONTEÚDO PRINCIPAL -->
    <div v-else>
      <v-alert v-if="projetosFiltrados.length === 0" type="info" variant="tonal" border="start" prominent>
        Nenhum projeto foi encontrado com os filtros selecionados.
      </v-alert>

      <div v-else>
        <!-- VISUALIZAÇÃO EM GRADE -->
        <v-row v-if="viewMode === 'grid'">
          <v-col v-for="projeto in projetosFiltrados" :key="projeto.id" cols="12" md="6">
            <v-card class="d-flex flex-column" height="100%" hover variant="outlined" @click="verDetalhesDoProjeto(projeto)" style="cursor: pointer;">
              <v-card-item>
                <div class="d-flex justify-space-between align-start">
                  <v-card-title class="text-wrap me-2 text-teal-darken-3">{{ projeto.titulo }}</v-card-title>
                  <v-chip :color="projeto.status.color" size="small" label variant="tonal">{{ projeto.status.text }}</v-chip>
                </div>
                <v-card-subtitle class="mt-1">{{ projeto.evento }}</v-card-subtitle>
              </v-card-item>
              <v-card-text>

                <div class="info-item">
                  <v-icon start color="grey-darken-1" size="small">mdi-account-group-outline</v-icon>
                  <span class="text-body-2"><strong>{{ projeto.membrosCount }}</strong> {{ projeto.membrosCount === 1 ? 'membro na equipe' : 'membros na equipe' }}</span>
                </div>
              </v-card-text>
              <v-spacer></v-spacer>
              <v-divider></v-divider>
              <v-card-actions class="pa-3">
                <v-spacer></v-spacer>
                <span class="text-body-2 text-grey-darken-1 mr-2">Ver detalhes e gerenciar</span>
                <v-icon color="teal-darken-2">mdi-arrow-right-circle-outline</v-icon>
              </v-card-actions>
            </v-card>
          </v-col>
        </v-row>
        
        <!-- VISUALIZAÇÃO EM LISTA -->
        <v-card v-else-if="viewMode === 'list'" variant="outlined">
          <v-table hover>
            <thead>
              <tr>
                <th class="text-left">Projeto</th>
                <th class="text-left d-none d-sm-table-cell">Status</th>
                <th class="text-left d-none d-md-table-cell">Membros</th>
                <th class="text-right">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="projeto in projetosFiltrados" :key="`list-${projeto.id}`" @click="verDetalhesDoProjeto(projeto)" style="cursor: pointer;">
                <td>
                  <div class="font-weight-bold">{{ projeto.titulo }}</div>
                  <div class="text-caption text-grey-darken-1">{{ projeto.evento }}</div>
                </td>
                <td class="d-none d-sm-table-cell">
                  <v-chip :color="projeto.status.color" size="small" label variant="tonal">{{ projeto.status.text }}</v-chip>
                </td>
                <td class="d-none d-md-table-cell">{{ projeto.membrosCount }}</td>
                <td class="text-right">
                  <v-btn color="grey-darken-2" variant="text">
                    Gerenciar <v-icon end>mdi-arrow-right</v-icon>
                  </v-btn>
                </td>
              </tr>
            </tbody>
          </v-table>
        </v-card>
      </div>
    </div>
  </v-container>
</template>

<style scoped>
.text-wrap {
  white-space: normal !important;
  word-break: break-word;
}
.info-item {
  display: flex;
  align-items: center;
  color: #424242;
}
</style>


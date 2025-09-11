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
let userId = null;

// --- OBTÉM DADOS DO USUÁRIO LOGADO ---
// Adicionamos este bloco para pegar o ID do usuário da sessão
const userDataString = sessionStorage.getItem('user_data');
if (userDataString) {
  const userData = JSON.parse(userDataString);
  userId = userData.user.id_usuario; // Armazena o ID do usuário logado
}

// --- LÓGICA DE BUSCA DE DADOS ---
onMounted(async () => {
  carregando.value = true;
  erro.value = null;

  // Verifica se o ID do usuário foi encontrado antes de fazer a chamada
  if (!userId) {
    erro.value = "Não foi possível identificar o usuário. Por favor, faça o login novamente.";
    notificationStore.showError(erro.value);
    carregando.value = false;
    return; // Interrompe a execução se não houver usuário
  }

  try {
    // **AQUI ESTÁ A CORREÇÃO**
    // Monta a URL dinamicamente com o ID do usuário logado
    const url = `/usuarios/${userId}/projetos/avaliacao`;
    console.log("Buscando projetos da URL:", url); // Ótimo para depuração
    
    const { data } = await api.get(url);
    
    projetos.value = data.map(transformarProjeto);

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
    evento: apiProjeto.evento?.nome || 'Evento não definido',
    area: apiProjeto.area_conhecimento || 'Área não definida',
    membrosCount: apiProjeto.membros_count || 0,
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
  // Ajuste a rota de destino se necessário
  router.push(`/projetos/orientados/${projeto.id}`);
};
</script>

<template>
  <v-container fluid>
    <v-row class="mb-6" align="center">
      <v-col cols="12">
        <h1 class="text-h4 font-weight-bold text-teal-darken-4">
          Meus Projetos Orientados
        </h1>
        <p class="text-subtitle-1 text-grey-darken-2">
          Gerencie os projetos que você está orientando.
        </p>
      </v-col>
    </v-row>

    <v-card class="mb-8 pa-4" variant="outlined">
      <v-text-field
        v-model="filtroBusca"
        label="Buscar por título do projeto..."
        prepend-inner-icon="mdi-magnify"
        variant="outlined"
        density="compact"
        hide-details
      ></v-text-field>
    </v-card>

    <div v-if="carregando">
      <v-row>
        <v-col v-for="n in 4" :key="n" cols="12" md="6">
          <v-skeleton-loader type="list-item-two-line"></v-skeleton-loader>
        </v-col>
      </v-row>
    </div>

    <v-alert v-else-if="erro" type="error" variant="tonal" border="start" prominent>
      {{ erro }}
    </v-alert>

    <div v-else>
      <v-alert v-if="projetosFiltrados.length === 0" type="info" variant="tonal" border="start" prominent>
        Nenhum projeto foi encontrado com os filtros selecionados.
      </v-alert>

      <v-row v-else>
        <v-col
          v-for="projeto in projetosFiltrados"
          :key="projeto.id"
          cols="12"
          md="6"
        >
          <v-card
            class="d-flex flex-column"
            height="100%"
            hover
            variant="outlined"
            @click="verDetalhesDoProjeto(projeto)"
            style="cursor: pointer;"
          >
            <v-card-item>
              <div class="d-flex justify-space-between align-start">
                <v-card-title class="text-wrap me-2 text-teal-darken-3">{{ projeto.titulo }}</v-card-title>
                <v-chip :color="projeto.status.color" size="small" label variant="tonal">
                  {{ projeto.status.text }}
                </v-chip>
              </div>
              <v-card-subtitle class="mt-1">{{ projeto.evento }}</v-card-subtitle>
            </v-card-item>

            <v-card-text>
              <div class="info-item mb-1">
                <v-icon start color="grey-darken-1" size="small">mdi-lightbulb-on-outline</v-icon>
                <span class="text-body-2">{{ projeto.area }}</span>
              </div>
              <div class="info-item">
                <v-icon start color="grey-darken-1" size="small">mdi-account-group-outline</v-icon>
                <span class="text-body-2">
                  <strong>{{ projeto.membrosCount }}</strong>
                  {{ projeto.membrosCount === 1 ? 'membro na equipe' : 'membros na equipe' }}
                </span>
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
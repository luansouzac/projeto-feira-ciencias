<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../assets/plugins/axios.js'; // Ajuste o caminho se necessário

const router = useRouter();

// --- ESTADOS DO COMPONENTE ---
const projetos = ref([]);
const carregando = ref(true);
const erro = ref(null);
const filtroBusca = ref('');
const filtroStatus = ref('Todos');
const viewMode = ref('grid'); // 'grid' ou 'list'

// --- LÓGICA DE BUSCA DE DADOS ---
onMounted(async () => {
  carregando.value = true;
  erro.value = null;
  try {
    const response = await api.get('/projetos');
    // Transforma os dados da API para o formato esperado pelo frontend
    projetos.value = response.data.map(transformarProjeto);
  } catch (err) {
    console.error("Erro ao buscar projetos:", err);
    erro.value = "Não foi possível carregar os projetos. Tente novamente mais tarde.";
  } finally {
    carregando.value = false;
  }
});

// --- FUNÇÃO PARA TRANSFORMAR OS DADOS DA API ---
const transformarProjeto = (apiProjeto) => {
  // Assumindo que a API retorna 'membros_count' e 'max_membros'. Ajuste se os nomes forem diferentes.
  const inscritos = apiProjeto.membros_count || 0;
  const maxAlunos = apiProjeto.max_membros || 5; // Um valor padrão caso não venha da API

  let status = 'Em Análise';
  let validado = false;

  // Lógica para definir o status e a validação com base na situação do projeto
  // Assumindo que id_situacao: 1 = Em Análise, 2 = Aprovado/Público. Ajuste conforme sua regra de negócio.
  if (apiProjeto.id_situacao > 1) { 
    validado = true;
    status = inscritos >= maxAlunos ? 'Lotado' : 'Vagas Abertas';
  }

  return {
    id: apiProjeto.id_projeto,
    titulo: apiProjeto.titulo,
    // Assumindo que a API retorna um objeto aninhado para o orientador
    orientador: apiProjeto.orientador?.nome || 'Orientador não definido',
    area: apiProjeto.area_conhecimento || 'Área não definida',
    status,
    validado,
    inscritos,
    maxAlunos,
  };
};

// --- CONFIGURAÇÕES E COMPUTEDS ---
const statusMap = {
  'Vagas Abertas': { color: 'green-darken-2' },
  'Lotado': { color: 'red-darken-2' },
  'Em Análise': { color: 'orange-darken-2' },
};

const statusOptions = ['Todos', 'Vagas Abertas', 'Lotado', 'Em Análise'];

const projetosFiltrados = computed(() => {
  return projetos.value.filter(p => {
    const correspondeBusca = p.titulo.toLowerCase().includes(filtroBusca.value.toLowerCase());
    const correspondeStatus = filtroStatus.value === 'Todos' || p.status === filtroStatus.value;
    return correspondeBusca && correspondeStatus;
  });
});

const getProgressoInscricao = (inscritos, max) => {
  if (max === 0) return 0;
  return (inscritos / max) * 100;
};

const getCorProgresso = (inscritos, max) => {
  const percentual = getProgressoInscricao(inscritos, max);
  if (percentual >= 100) return 'red-darken-1';
  if (percentual > 70) return 'orange-darken-1';
  return 'green-darken-1';
};

// --- FUNÇÕES DE INTERAÇÃO ---
const abrirModalNovoProjeto = () => {
  console.log("Abrir modal para cadastrar novo projeto.");
};

const gerenciarProjeto = (id) => {
  router.push(`/gerenciar-projeto/${id}`); // Exemplo de rota
};
</script>

<template>
  <v-container fluid>
    <!-- CABEÇALHO DA PÁGINA -->
    <v-row class="mb-6" align="center">
      <v-col cols="12" md="8">
        <h1 class="text-h4 font-weight-bold text-green-darken-4">Banco de Projetos</h1>
        <p class="text-subtitle-1 text-grey-darken-2">
          Visualize, gerencie e cadastre novas propostas de projetos para os alunos.
        </p>
      </v-col>
      <v-col cols="12" md="4" class="text-md-right">
        <v-btn
          color="green-darken-3"
          size="large"
          prepend-icon="mdi-plus-box-outline"
          @click="abrirModalNovoProjeto"
          block
        >
          Cadastrar Novo Projeto
        </v-btn>
      </v-col>
    </v-row>

    <!-- FILTROS -->
    <v-card class="mb-8 pa-4" variant="outlined">
      <v-row align="center">
        <v-col cols="12" md="6">
          <v-text-field
            v-model="filtroBusca"
            label="Buscar por título do projeto..."
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
            density="compact"
            hide-details
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="6" class="d-flex align-center justify-start justify-md-end">
          <v-chip-group
            v-model="filtroStatus"
            mandatory
            color="green-darken-3"
          >
            <v-chip
              v-for="status in statusOptions"
              :key="status"
              :value="status"
              filter
            >
              {{ status }}
            </v-chip>
          </v-chip-group>
          <v-btn-toggle
              v-model="viewMode"
              mandatory
              density="compact"
              variant="outlined"
              class="ml-4"
            >
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
        <v-col v-for="n in 6" :key="n" cols="12" sm="6" lg="4">
          <v-skeleton-loader type="card"></v-skeleton-loader>
        </v-col>
      </v-row>
    </div>

    <!-- ESTADO DE ERRO -->
    <div v-else-if="erro">
        <v-alert type="error" variant="tonal" border="start" prominent>
          {{ erro }}
        </v-alert>
    </div>

    <!-- ÁREA DE CONTEÚDO -->
    <div v-else>
      <div v-if="projetosFiltrados.length === 0">
        <v-alert type="info" variant="tonal" border="start" prominent>
          Nenhum projeto encontrado com os filtros selecionados.
        </v-alert>
      </div>
      <div v-else>
        <!-- VISUALIZAÇÃO EM GRADE -->
        <v-row v-if="viewMode === 'grid'">
          <v-col v-for="projeto in projetosFiltrados" :key="projeto.id" cols="12" sm="6" lg="4">
            <v-card class="d-flex flex-column" height="100%" hover variant="outlined">
              <v-card-item class="pb-2">
                <div class="d-flex justify-space-between align-start">
                  <v-card-title class="text-wrap me-2">{{ projeto.titulo }}</v-card-title>
                  <v-chip :color="statusMap[projeto.status].color" size="small" label variant="tonal">
                    {{ projeto.status }}
                  </v-chip>
                </div>
                <v-card-subtitle v-if="!projeto.validado" class="d-flex align-center text-orange-darken-3 mt-1">
                  <v-icon size="xs" start>mdi-eye-off-outline</v-icon>
                  Visível apenas para professores
                </v-card-subtitle>
                <v-card-subtitle v-else class="d-flex align-center text-green-darken-3 mt-1">
                  <v-icon size="xs" start>mdi-eye-outline</v-icon>
                  Público para inscrições
                </v-card-subtitle>
              </v-card-item>
              <v-card-text class="py-3">
                <div>
                  <div class="info-item">
                    <v-icon start color="grey-darken-1" size="small">mdi-account-tie</v-icon>
                    <span class="font-weight-medium text-body-2">{{ projeto.orientador }}</span>
                  </div>
                  <div class="info-item mt-1">
                    <v-icon start color="grey-darken-1" size="small">mdi-lightbulb-on-outline</v-icon>
                    <span class="text-body-2">{{ projeto.area }}</span>
                  </div>
                </div>
                <div class="mt-4">
                  <div class="d-flex justify-space-between align-center mb-1">
                    <span class="text-body-2 font-weight-medium text-grey-darken-3">Inscrições</span>
                    <span class="font-weight-bold" :class="`text-${getCorProgresso(projeto.inscritos, projeto.maxAlunos)}`">
                      {{ projeto.inscritos }} / {{ projeto.maxAlunos }}
                    </span>
                  </div>
                  <v-progress-linear :model-value="getProgressoInscricao(projeto.inscritos, projeto.maxAlunos)" :color="getCorProgresso(projeto.inscritos, projeto.maxAlunos)" height="7" rounded></v-progress-linear>
                </div>
              </v-card-text>
              <v-spacer></v-spacer>
              <v-divider></v-divider>
              <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="green-darken-3" variant="text" @click="gerenciarProjeto(projeto.id)">
                  Gerenciar<v-icon end>mdi-arrow-right</v-icon>
                </v-btn>
              </v-card-actions>
            </v-card>
          </v-col>
        </v-row>
        
        <!-- VISUALIZAÇÃO EM LISTA -->
         <v-card v-if="viewMode === 'list'" variant="outlined">
          <v-table hover>
            <thead>
              <tr>
                <th class="text-left">Projeto</th>
                <th class="text-left d-none d-md-table-cell">Inscrições</th>
                <th class="text-left d-none d-sm-table-cell">Status</th>
                <th class="text-right">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="projeto in projetosFiltrados" :key="`list-${projeto.id}`">
                <td>
                  <div class="font-weight-bold">{{ projeto.titulo }}</div>
                  <div class="text-caption text-grey-darken-1">{{ projeto.orientador }}</div>
                  <v-card-subtitle v-if="!projeto.validado" class="d-flex align-center text-orange-darken-3 pa-0 mt-1" style="font-size: 0.7rem;">
                    <v-icon size="xs" start>mdi-eye-off-outline</v-icon>
                    Apenas professores
                  </v-card-subtitle>
                  <v-card-subtitle v-else class="d-flex align-center text-green-darken-3 pa-0 mt-1" style="font-size: 0.7rem;">
                    <v-icon size="xs" start>mdi-eye-outline</v-icon>
                    Público
                  </v-card-subtitle>
                </td>
                <td class="d-none d-md-table-cell">
                  <div class="d-flex align-center" style="min-width: 150px;">
                    <v-progress-linear :model-value="getProgressoInscricao(projeto.inscritos, projeto.maxAlunos)" :color="getCorProgresso(projeto.inscritos, projeto.maxAlunos)" height="6" rounded class="mr-3"></v-progress-linear>
                    <span class="font-weight-medium text-body-2">{{ projeto.inscritos }}/{{ projeto.maxAlunos }}</span>
                  </div>
                </td>
                <td class="d-none d-sm-table-cell">
                  <v-chip :color="statusMap[projeto.status].color" size="small" label variant="tonal">
                    {{ projeto.status }}
                  </v-chip>
                </td>
                <td class="text-right">
                  <v-btn color="grey-darken-2" variant="text" @click="gerenciarProjeto(projeto.id)">
                    Gerenciar
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
  color: #424242; /* Cinza escuro para texto */
}
</style>


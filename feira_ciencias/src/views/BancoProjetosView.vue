<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../assets/plugins/axios.js'
import CrudModal from '@/components/CrudModal.vue'
import { useNotificationStore } from '@/stores/notification'
import { useEventoStore } from '@/stores/eventoStore'
import { storeToRefs } from 'pinia'

const router = useRouter()
const notificationStore = useNotificationStore()
const eventoStore = useEventoStore()
const { eventos } = storeToRefs(eventoStore)

// --- ESTADOS DA PÁGINA ---
const projetos = ref([])
const carregando = ref(true)
const erro = ref(null)
const filtroBusca = ref('')
const filtroStatus = ref('Todos')
const viewMode = ref('grid')
const avaliadores = ref([])
let userId = null
const userType = ref(null)
const selectedProject = ref(null) // Corrigido para null
const nenhumProjetoAprovado = ref(false)

// --- ESTADOS DO MODAL ---
const isModalOpen = ref(false)
const isModalLoading = ref(false)
const showConfirmDialog = ref(false)

const getInitialFormData = () => ({
  id_evento: null,
  titulo: '',
  problema: '',
  relevancia: '',
  max_pessoas: 5,
  id_orientador: null,
  id_coorientador: null,
})
const currentItem = ref(getInitialFormData())

// --- OBTÉM DADOS DO USUÁRIO LOGADO ---
const userDataString = sessionStorage.getItem('user_data')
if (userDataString) {
  const userData = JSON.parse(userDataString)
  userId = userData.user.id_usuario
  userType.value = userData.user.id_tipo_usuario
}

// --- LÓGICA DE BUSCA DE DADOS ---
onMounted(async () => {
  carregando.value = true
  erro.value = null

  const fetchProjetosPromise = api.get('/projetos')
  const fetchAvaliadoresPromise = api.get('/usuarios?id_tipo_usuario=4')
  const fetchEventosPromise = eventoStore.fetchEventos()

  try {
    const results = await Promise.allSettled([
      fetchProjetosPromise,
      fetchAvaliadoresPromise,
      fetchEventosPromise,
    ])

    const [projetosResult, avaliadoresResult, eventosResult] = results

    if (projetosResult.status === 'fulfilled') {
      let dadosProjetos = projetosResult.value.data

      if (userType.value === 2) {
        const projetosAprovados = dadosProjetos.filter((p) => p.id_situacao > 1)
        if (projetosAprovados.length === 0) {
          nenhumProjetoAprovado.value = true
        }
        dadosProjetos = projetosAprovados
      }
      projetos.value = dadosProjetos.map(transformarProjeto)
    } else {
      throw new Error('Não foi possível carregar os projetos.')
    }

    if (avaliadoresResult.status === 'fulfilled') {
      avaliadores.value = avaliadoresResult.value.data
    } else {
      console.error('Erro ao buscar avaliadores:', avaliadoresResult.reason)
    }

    if (eventosResult.status === 'rejected') {
      console.error('Erro ao buscar eventos:', eventosResult.reason)
    }
  } catch (err) {
    erro.value = err.message || 'Ocorreu um erro inesperado.'
  } finally {
    carregando.value = false
  }
})

// --- CONFIGURAÇÃO DO MODAL ---
const modalConfig = computed(() => ({
  title: 'Cadastrar Novo Projeto',
  fields: [
    { key: 'id_evento', label: 'Evento Associado', type: 'select', items: eventos.value.map(e => ({ title: e.nome, value: e.id_evento })), rules: [v => !!v || 'É necessário selecionar um evento'] },
    { key: 'titulo', label: 'Título do Projeto', type: 'text', rules: [v => !!v || 'O título é obrigatório'] },
    { key: 'problema', label: 'Problema a ser Resolvido', type: 'textarea', rules: [v => !!v || 'A descrição do problema é obrigatória'] },
    { key: 'relevancia', label: 'Relevância do Projeto', type: 'textarea', rules: [v => !!v || 'A relevância é obrigatória'] },
    { key: 'max_pessoas', label: 'Nº Máximo de Participantes', type: 'number', rules: [v => !!v || 'O nº máximo é obrigatório', v => v > 0 || 'Deve ser maior que zero'] },
    { key: 'id_orientador', label: 'Professor Orientador', type: 'select', items: avaliadores.value.map(a => ({ title: a.nome, value: a.id_usuario })), rules: [v => !!v || 'O orientador é obrigatório'] },
    { key: 'id_coorientador', label: 'Professor Coorientador (Opcional)', type: 'select', items: avaliadores.value.map(a => ({ title: a.nome, value: a.id_usuario })) },
  ],
}));

// --- FUNÇÕES DE TRANSFORMAÇÃO E LÓGICA DE VISUALIZAÇÃO ---

// NOVO: VERIFICA SE O ALUNO JÁ TEM INSCRIÇÃO EM ALGUM PROJETO
const alunoJaInscrito = computed(() => {
  if (userType.value !== 2) return false; // Regra só se aplica a alunos
  return projetos.value.some(p => p.alunoInscrito);
});

const transformarProjeto = (apiProjeto) => {
  const inscritos = apiProjeto.equipe?.[0]?.membro_equipe?.length ?? 0
  const maxAlunos = apiProjeto.max_pessoas || apiProjeto.eventos?.max_pessoas || 5
  let status = 'Em Análise'
  let validado = false

  if (apiProjeto.id_situacao > 1) {
    validado = true
    status = inscritos >= maxAlunos ? 'Esgotado' : 'Vagas Abertas'
  }
  const alunoInscrito =
    apiProjeto.equipe?.[0]?.membro_equipe?.some((m) => m.id_usuario === userId) ?? false

  return {
    id: apiProjeto.id_projeto,
    titulo: apiProjeto.titulo,
    orientador: apiProjeto.orientador?.nome || 'Não definido',
    area: apiProjeto.area_conhecimento || 'Não definida',
    status,
    validado,
    inscritos,
    maxAlunos,
    alunoInscrito,
  }
}

const statusMap = {
  'Vagas Abertas': { color: 'green-darken-2' },
  Esgotado: { color: 'red-darken-2' },
  'Em Análise': { color: 'orange-darken-2' },
}

const statusOptions = computed(() => {
  if (userType.value === 2) return ['Todos', 'Vagas Abertas', 'Esgotado']
  return ['Todos', 'Vagas Abertas', 'Esgotado', 'Em Análise']
})

const projetosFiltrados = computed(() => {
  return projetos.value.filter((p) => {
    const correspondeBusca = p.titulo.toLowerCase().includes(filtroBusca.value.toLowerCase())
    const correspondeStatus = filtroStatus.value === 'Todos' || p.status === filtroStatus.value
    return correspondeBusca && correspondeStatus
  })
})

const getProgressoInscricao = (inscritos, max) => (max > 0 ? (inscritos / max) * 100 : 0)

const getCorProgresso = (inscritos, max) => {
  const percentual = getProgressoInscricao(inscritos, max)
  if (percentual >= 100) return 'red-darken-1'
  if (percentual > 70) return 'orange-darken-1'
  return 'green-darken-1'
}

// --- FUNÇÕES DE AÇÃO ---
const openCreateModal = () => {
  currentItem.value = getInitialFormData();
  isModalOpen.value = true;
};

const handleSave = async (formData) => {
  isModalLoading.value = true;
  try {
    const situacaoProjeto = userType.value === 4 ? 2 : 1;
    const payloadProjeto = { ...formData, id_responsavel: userId, id_situacao: situacaoProjeto };
    const { data: responseData } = await api.post('/projetos', payloadProjeto);
    const novoProjeto = responseData.data || responseData;

    if (!novoProjeto || !novoProjeto.id_projeto) {
      throw new Error('A API não retornou um projeto válido.');
    }

    const payloadEquipe = {
      id_projeto: novoProjeto.id_projeto,
      nome_equipe: `Equipe - ${novoProjeto.titulo}`
    };
    await api.post('/equipes', payloadEquipe);

    novoProjeto.equipe = [{ membro_equipe: [] }];
    projetos.value.unshift(transformarProjeto(novoProjeto));
    
    notificationStore.showSuccess('Projeto cadastrado com sucesso!');
    isModalOpen.value = false;
  } catch (error) {
    console.error("Erro ao cadastrar o projeto:", error);
    const errorMessage = error.response?.data?.message || 'Ocorreu um erro.';
    notificationStore.showError(errorMessage);
  } finally {
    isModalLoading.value = false;
  }
};

const gerenciarProjeto = (id) => {
  router.push(`/gerenciar-projeto/${id}`)
}

const verDetalhes = (id) => {
  router.push(`/projetos/${id}`)
}

const inscreverNoProjeto = async (projeto) => {
  notificationStore.showInfo(`Enviando inscrição para "${projeto.titulo}"...`)
  try {
    await api.post(`/projetos/${projeto.id}/inscrever`)
    notificationStore.showSuccess('Inscrição realizada com sucesso!')
    projeto.alunoInscrito = true
    projeto.inscritos++
    if (projeto.inscritos >= projeto.maxAlunos) {
      projeto.status = 'Esgotado'
    }
  } catch (err) {
    console.error('Erro ao inscrever no projeto:', err)
    const mensagemErro = err.response?.data?.erro || 'Não foi possível se inscrever.'
    notificationStore.showError(mensagemErro)
  }
}

const sairDoProjeto = (projeto) => {
  selectedProject.value = projeto
  showConfirmDialog.value = true
}

const cancelDialog = () => {
  showConfirmDialog.value = false
}

const confirmDialog = async () => {
  if (!selectedProject.value) return;
  notificationStore.showInfo(`Cancelando inscrição em "${selectedProject.value.titulo}"...`)
  try {
    await api.post(`/projetos/desinscrever/${selectedProject.value.id}/${userId}`)
    notificationStore.showSuccess('Inscrição cancelada com sucesso!')
    
    selectedProject.value.alunoInscrito = false
    selectedProject.value.inscritos--
    if (selectedProject.value.inscritos < selectedProject.value.maxAlunos) {
      selectedProject.value.status = 'Vagas Abertas'
    }
  } catch (err) {
    console.error('Erro ao sair do projeto:', err)
    const mensagemErro = err.response?.data?.erro || 'Não foi possível sair do projeto.'
    notificationStore.showError(mensagemErro)
  } finally {
    showConfirmDialog.value = false
    selectedProject.value = null;
  }
}
</script>

<template>
  <v-container fluid>
    <v-row class="mb-6" align="center">
      <v-col cols="12" md="8">
        <h1 class="text-h4 font-weight-bold text-green-darken-4">
          {{ userType === 2 ? 'Projetos Disponíveis' : 'Banco de Projetos' }}
        </h1>
        <p class="text-subtitle-1 text-grey-darken-2">
          {{ userType === 2 ? 'Explore os temas e inscreva-se em um projeto.' : 'Visualize, gerencie e cadastre novas propostas.' }}
        </p>
      </v-col>
      <v-col v-if="userType === 4" cols="12" md="4" class="text-md-right">
        <v-btn
          color="green-darken-3"
          size="large"
          prepend-icon="mdi-plus-box-outline"
          @click="openCreateModal"
          block
        >
          Cadastrar Novo Projeto
        </v-btn>
      </v-col>
    </v-row>

    <v-dialog v-model="showConfirmDialog" max-width="500px">
      <v-card>
        <v-card-title class="headline">
          Cancelar Inscrição
        </v-card-title>
        <v-card-text>
          Tem certeza que deseja sair da equipe deste projeto?
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey-darken-1" text @click="cancelDialog">Voltar</v-btn>
          <v-btn color="red-darken-1" variant="flat" @click="confirmDialog">Confirmar Saída</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

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
          <v-chip-group v-model="filtroStatus" mandatory color="green-darken-3">
            <v-chip v-for="status in statusOptions" :key="status" :value="status" filter>
              {{ status }}
            </v-chip>
          </v-chip-group>
          <v-btn-toggle v-model="viewMode" mandatory density="compact" variant="outlined" class="ml-4">
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

    <div v-if="carregando">
      <v-row>
        <v-col v-for="n in 6" :key="n" cols="12" sm="6" lg="4">
          <v-skeleton-loader type="card"></v-skeleton-loader>
        </v-col>
      </v-row>
    </div>

    <v-alert v-else-if="erro" type="error" variant="tonal" border="start" prominent>{{ erro }}</v-alert>

    <div v-else>
      <v-alert
        v-if="userType === 2 && nenhumProjetoAprovado"
        type="info" variant="tonal" border="start" prominent
        icon="mdi-clock-time-three-outline"
      >
        <v-alert-title class="font-weight-bold">Inscrições em Breve!</v-alert-title>
        As inscrições para os projetos aprovados ainda não abriram!
      </v-alert>

      <v-alert
        v-else-if="projetosFiltrados.length === 0"
        type="info" variant="tonal" border="start" prominent
      >
        Nenhum projeto encontrado com os filtros selecionados.
      </v-alert>

      <div v-else>
        <v-row v-if="viewMode === 'grid'">
          <v-col v-for="projeto in projetosFiltrados" :key="projeto.id" cols="12" sm="6" lg="4">
             <v-card class="d-flex flex-column" height="100%" hover variant="outlined">
                <v-card-item class="pb-2">
                  <div class="d-flex justify-space-between align-start">
                    <v-card-title class="text-wrap me-2">{{ projeto.titulo }}</v-card-title>
                    <v-chip :color="statusMap[projeto.status].color" size="small" label variant="tonal">{{ projeto.status }}</v-chip>
                  </div>
                    <v-card-subtitle v-if="userType !== 2 && !projeto.validado" class="d-flex align-center text-orange-darken-3 mt-1">
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
                    <div class="info-item"><v-icon start color="grey-darken-1" size="small">mdi-account-tie</v-icon><span class="font-weight-medium text-body-2">{{ projeto.orientador }}</span></div>
                    <div class="info-item mt-1"><v-icon start color="grey-darken-1" size="small">mdi-lightbulb-on-outline</v-icon><span class="text-body-2">{{ projeto.area }}</span></div>
                  </div>
                  <div class="mt-4">
                    <div class="d-flex justify-space-between align-center mb-1">
                      <span class="text-body-2 font-weight-medium text-grey-darken-3">Inscrições</span>
                      <span class="font-weight-bold" :class="`text-${getCorProgresso(projeto.inscritos, projeto.maxAlunos)}`">{{ projeto.inscritos }} / {{ projeto.maxAlunos }}</span>
                    </div>
                    <v-progress-linear :model-value="getProgressoInscricao(projeto.inscritos, projeto.maxAlunos)" :color="getCorProgresso(projeto.inscritos, projeto.maxAlunos)" height="7" rounded></v-progress-linear>
                  </div>
                </v-card-text>
                <v-spacer></v-spacer>
                <v-divider></v-divider>
                <v-card-actions class="pa-2">
                  <template v-if="userType !== 2">
                    <v-spacer></v-spacer>
                    <v-btn color="grey-darken-3" variant="text" @click="verDetalhes(projeto.id)">Detalhes</v-btn>
                    <v-btn color="green-darken-3" variant="text" @click="gerenciarProjeto(projeto.id)">Gerenciar<v-icon end>mdi-arrow-right</v-icon></v-btn>
                  </template>
                  <template v-else>
                    <v-btn variant="text" @click="verDetalhes(projeto.id)">Ver Detalhes</v-btn>
                    <v-spacer></v-spacer>
                    
                    <v-btn v-if="projeto.alunoInscrito" color="red-darken-2" variant="text" @click="sairDoProjeto(projeto)">
                      Sair
                      <v-icon end>mdi-logout</v-icon>
                    </v-btn>

                    <v-btn
                      v-else
                      :disabled="projeto.status === 'Esgotado' || alunoJaInscrito"
                      color="green-darken-3"
                      variant="flat"
                      @click="inscreverNoProjeto(projeto)"
                    >
                       {{ alunoJaInscrito ? 'Inscrição única' : 'Inscrever-se' }}
                    </v-btn>
                  </template>
                </v-card-actions>
              </v-card>
          </v-col>
        </v-row>
        
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
                  <v-card-subtitle v-if="userType !== 2 && !projeto.validado" class="d-flex align-center text-orange-darken-3 pa-0 mt-1" style="font-size: 0.7rem;"><v-icon size="xs" start>mdi-eye-off-outline</v-icon>Apenas professores</v-card-subtitle>
                  <v-card-subtitle v-else class="d-flex align-center text-green-darken-3 pa-0 mt-1" style="font-size: 0.7rem;"><v-icon size="xs" start>mdi-eye-outline</v-icon>Público</v-card-subtitle>
                </td>
                <td class="d-none d-md-table-cell">
                  <div class="d-flex align-center" style="min-width: 150px;">
                    <v-progress-linear :model-value="getProgressoInscricao(projeto.inscritos, projeto.maxAlunos)" :color="getCorProgresso(projeto.inscritos, projeto.maxAlunos)" height="6" rounded class="mr-3"></v-progress-linear>
                    <span class="font-weight-medium text-body-2">{{ projeto.inscritos }}/{{ projeto.maxAlunos }}</span>
                  </div>
                </td>
                <td class="d-none d-sm-table-cell"><v-chip :color="statusMap[projeto.status].color" size="small" label variant="tonal">{{ projeto.status }}</v-chip></td>
                <td class="text-right">
                   <div v-if="userType !== 2">
                     <v-btn size="small" variant="text" @click="verDetalhes(projeto.id)" class="mr-1">Detalhes</v-btn>
                     <v-btn size="small" color="green-darken-3" variant="text" @click="gerenciarProjeto(projeto.id)">Gerenciar</v-btn>
                   </div>
                   <div v-else class="d-flex justify-end align-center">
                     <v-btn size="small" variant="text" @click="verDetalhes(projeto.id)" class="mr-1">Detalhes</v-btn>
                     
                      <v-btn
                        v-if="projeto.alunoInscrito"
                        size="small"
                        color="red-darken-2"
                        variant="text"
                        @click="sairDoProjeto(projeto)"
                      >
                       Sair <v-icon size="small" end>mdi-logout</v-icon>
                     </v-btn>
                     
                      <v-btn
                        v-else
                        size="small"
                        :disabled="projeto.status === 'Esgotado' || alunoJaInscrito"
                        color="green-darken-3"
                        variant="tonal"
                        @click="inscreverNoProjeto(projeto)"
                      >
                       Inscrever-se
                     </v-btn>
                   </div>
                </td>
              </tr>
            </tbody>
          </v-table>
        </v-card>
      </div>
    </div>

    <CrudModal
      v-model="isModalOpen"
      :title="modalConfig.title"
      :fields="modalConfig.fields"
      :item="currentItem"
      :loading="isModalLoading"
      @save="handleSave"
    />
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

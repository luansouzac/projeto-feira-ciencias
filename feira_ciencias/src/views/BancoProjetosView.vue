<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/assets/plugins/axios.js'
import { useNotificationStore } from '@/stores/notification'
import { useAuthStore } from '@/stores/authStore'
import { useEventoStore } from '@/stores/eventoStore'
import { storeToRefs } from 'pinia'
import ProjectCard from '@/components/ProjectCard.vue'
import CrudModal from '@/components/CrudModal.vue'

// --- INSTÂNCIAS E STORES ---
const router = useRouter()
const notificationStore = useNotificationStore()
const authStore = useAuthStore()
const eventoStore = useEventoStore()
const { eventos } = storeToRefs(eventoStore)

// --- ESTADOS DA PÁGINA ---
const projetos = ref([])
const carregando = ref(true)
const erro = ref(null)
const filtroBusca = ref('')
const filtroStatus = ref('Todos')
const filtroEvento = ref(null)
const viewMode = ref('grid')
const avaliadores = ref([])

// --- ESTADOS DE CONTROLE ---
const userId = authStore.user?.id_usuario
const userType = authStore.user?.id_tipo_usuario
const isModalOpen = ref(false)
const isModalLoading = ref(false)
const showConfirmDialog = ref(false)
const selectedProject = ref(null)
const currentItem = ref({})

// --- COMPUTEDS DE CONTROLE DE PAPEL ---
const isAluno = computed(() => userType === 2)
const isProfessor = computed(() => userType === 4)

// --- LÓGICA DE BUSCA DE DADOS (CONDICIONAL) ---
onMounted(async () => {
  carregando.value = true
  erro.value = null
  try {
    const { data: apiProjetos } = await api.get('/projetos')
    await eventoStore.fetchEventos() 
    projetos.value = apiProjetos.map(transformarProjeto)
  } catch (err) {
    console.error("Erro ao buscar dados:", err)
    erro.value = "Não foi possível carregar os dados."
  } finally {
    carregando.value = false
  }
})

// --- FUNÇÕES DE TRANSFORMAÇÃO DE DADOS ---
function transformarProjeto(apiProjeto) {
  const inscritos = apiProjeto.equipe?.membro_equipe?.length ?? 0
  const maxAlunos = apiProjeto.max_pessoas ?? 5
  const alunoInscrito = apiProjeto.equipe?.membro_equipe?.some(m => m.id_usuario === userId) ?? false

  let statusParaCard
  if (isAluno.value) {
    statusParaCard = inscritos >= maxAlunos ? 'Esgotado' : 'Vagas Abertas'
  } else {
    const statusMapProfessor = { 1: 'Em Análise', 2: 'Aprovado', 3: 'Reprovado', 4: 'Com Ressalvas' }
    statusParaCard = statusMapProfessor[apiProjeto.id_situacao] || 'Pendente'
  }

  let statusInscricao = 'INDISPONIVEL'
  let mensagemInscricao = 'Período de inscrição não definido.'
  if (apiProjeto.eventos?.inicio_inscricao && apiProjeto.eventos?.fim_inscricao) {
    const agora = new Date()
    const inicio = new Date(apiProjeto.eventos.inicio_inscricao)
    const fim = new Date(apiProjeto.eventos.fim_inscricao)
    fim.setHours(23, 59, 59, 999)

    if (agora < inicio) {
      statusInscricao = 'NAO_INICIADO'
      mensagemInscricao = `Inscrições abrem em ${inicio.toLocaleDateString('pt-BR')}`
    } else if (agora > fim) {
      statusInscricao = 'ENCERRADO'
      mensagemInscricao = 'Período de inscrições encerrado.'
    } else {
      statusInscricao = 'ABERTO'
      mensagemInscricao = 'Inscrições abertas!'
    }
  }

  return {
    ...apiProjeto,
    id: apiProjeto.id_projeto,
    status: statusParaCard,
    inscritos,
    maxAlunos,
    alunoInscrito,
    statusInscricao,
    mensagemInscricao,
  }
}

// --- COMPUTEDS PARA FILTRAGEM E VISUALIZAÇÃO ---
const alunoJaInscritoNoEvento = (eventoId) => {
  if (!eventoId || !isAluno.value) return false
  return projetos.value.some(p => p.id_evento === eventoId && p.alunoInscrito)
}

const statusOptions = computed(() => {
  if (isAluno.value) return ['Todos', 'Vagas Abertas', 'Esgotado']
  if (isProfessor.value) return ['Todos', 'Em Análise', 'Aprovado', 'Reprovado', 'Com Ressalvas']
  return ['Todos']
})

const eventosParaFiltro = computed(() => {
  const listaEventos = eventos.value.map(e => ({ title: e.nome, value: e.id_evento }));
  return [{ title: 'Todos os Eventos', value: null }, ...listaEventos];
});

const projetosPorEvento = computed(() => {
  let projetosVisiveis = projetos.value;

  if (isAluno.value) {
    projetosVisiveis = projetos.value.filter(p => p.id_situacao === 2 || p.id_situacao === 4);
  }

  const projetosFiltrados = projetosVisiveis.filter(p => {
    const correspondeBusca = p.titulo.toLowerCase().includes(filtroBusca.value.toLowerCase());
    const correspondeStatus = filtroStatus.value === 'Todos' || p.status === filtroStatus.value;
    const correspondeEvento = !filtroEvento.value || p.id_evento === filtroEvento.value;
    return correspondeBusca && correspondeStatus && correspondeEvento;
  });

  const grupos = projetosFiltrados.reduce((acc, projeto) => {
    const eventoId = projeto.id_evento || 'sem-evento';
    if (!acc[eventoId]) {
      acc[eventoId] = {
        evento: eventos.value.find(e => e.id_evento === eventoId) || { id_evento: 'sem-evento', nome: 'Projetos sem Evento Associado' },
        projetos: [],
      };
    }
    acc[eventoId].projetos.push(projeto);
    return acc;
  }, {});

  return Object.values(grupos);
});


// --- FUNÇÕES DE AÇÃO ---
const inscreverNoProjeto = async (projeto) => {
  notificationStore.showInfo(`Enviando inscrição para "${projeto.titulo}"...`)
  try {
    await api.post(`/projetos/${projeto.id}/inscrever`)
    notificationStore.showSuccess('Inscrição realizada com sucesso!')
    
    const projetoOriginal = projetos.value.find(p => p.id === projeto.id)
    if (projetoOriginal) {
      projetoOriginal.alunoInscrito = true
      projetoOriginal.inscritos++
      if (projetoOriginal.inscritos >= projetoOriginal.maxAlunos) {
        projetoOriginal.status = 'Esgotado'
      }
    }
  } catch (err) {
    console.error('Erro ao inscrever no projeto:', err)
    notificationStore.showError(err.response?.data?.message || 'Não foi possível se inscrever.')
  }
}

const sairDoProjeto = (projeto) => {
  selectedProject.value = projeto
  showConfirmDialog.value = true
}

const confirmSairDoProjeto = async () => {
  if (!selectedProject.value) return
  notificationStore.showInfo(`Cancelando inscrição...`)
  try {
    const projeto = selectedProject.value
    await api.post(`/projetos/desinscrever/${projeto.equipe.id_equipe}/${userId}`)
    notificationStore.showSuccess('Inscrição cancelada com sucesso!')
    
    const projetoOriginal = projetos.value.find(p => p.id === projeto.id)
    if (projetoOriginal) {
      projetoOriginal.alunoInscrito = false
      projetoOriginal.inscritos--
      if (projetoOriginal.inscritos < projetoOriginal.maxAlunos) {
        projetoOriginal.status = 'Vagas Abertas'
      }
    }
  } catch (err) {
    console.error('Erro ao sair do projeto:', err)
    notificationStore.showError(err.response?.data?.message || 'Não foi possível sair do projeto.')
  } finally {
    showConfirmDialog.value = false
    selectedProject.value = null
  }
}

const verDetalhes = (id) => router.push(`/projetos/${id}`)
const gerenciarProjeto = (id) => router.push(`/gerenciar-projeto/${id}`)
</script>

<template>
  <v-container fluid>
    <v-row class="mb-6" align="center">
      <v-col cols="12" md="8">
        <h1 v-if="isAluno" class="text-h4 font-weight-bold text-green-darken-4">Projetos para Inscrição</h1>
        <h1 v-if="isProfessor" class="text-h4 font-weight-bold text-green-darken-4">Galeria de Projetos</h1>
        <p class="text-subtitle-1 text-grey-darken-2">
          {{ isAluno ? 'Explore os temas e inscreva-se em um projeto por evento.' : 'Visualize e gerencie todos os projetos submetidos.' }}
        </p>
      </v-col>
      <v-col v-if="isProfessor" cols="12" md="4" class="text-md-right">
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

    <v-card class="mb-8 pa-4" variant="outlined">
      <v-row align="center" no-gutters>
        <v-col cols="12" md="4" class="pa-1">
          <v-text-field v-model="filtroBusca" label="Buscar por título..." prepend-inner-icon="mdi-magnify" variant="outlined" density="compact" hide-details clearable></v-text-field>
        </v-col>
        <v-col cols="12" md="4" class="pa-1">
          <v-select v-model="filtroEvento" :items="eventosParaFiltro" label="Filtrar por evento" variant="outlined" density="compact" hide-details clearable></v-select>
        </v-col>
        <v-col cols="12" md="4" class="d-flex align-center justify-start justify-md-end mt-4 mt-md-0 pa-1">
          <v-chip-group v-model="filtroStatus" mandatory color="green-darken-3">
            <v-chip v-for="status in statusOptions" :key="status" :value="status" filter size="small">{{ status }}</v-chip>
          </v-chip-group>

        </v-col>
      </v-row>
    </v-card>

    <div v-if="carregando">
      <v-row>
        <v-col v-for="n in 6" :key="n" cols="12" sm="6" lg="4">
          <v-skeleton-loader type="image, article, actions"></v-skeleton-loader>
        </v-col>
      </v-row>
    </div>

    <v-alert v-else-if="erro" type="error" variant="tonal">{{ erro }}</v-alert>

    <div v-else-if="projetosPorEvento.length === 0" class="text-center pa-16">
        <v-icon size="60" class="mb-4 text-grey-lighten-1">mdi-folder-search-outline</v-icon>
        <p class="text-h6 text-grey-darken-1">
          {{ filtroBusca || filtroStatus !== 'Todos' || filtroEvento ? 'Nenhum projeto encontrado para os filtros aplicados.' : 'Nenhum projeto disponível no momento.' }}
        </p>
    </div>
    
    <div v-else class="d-flex flex-column ga-8">
      <v-card 
        v-for="grupo in projetosPorEvento" 
        :key="grupo.evento.id_evento" 
        variant="outlined"
      >
        <v-card-title class="bg-grey-lighten-5">
          <span class="text-h6 font-weight-medium text-grey-darken-3">{{ grupo.evento.nome }}</span>
        </v-card-title>
        <v-divider></v-divider>

        <v-card-text>
          <v-row v-if="viewMode === 'grid'">
            <v-col v-for="projeto in grupo.projetos" :key="projeto.id" cols="12" md="6" lg="4">
              <ProjectCard
               :projeto="projeto"
               contexto= "inscricao"
               :inscrito="projeto.alunoInscrito"
               @ver-detalhes="verDetalhes(projeto.id)"
               @ver-resultados="goToResults(projeto.id)"
              >
                <template #actions>
                  <template v-if="isAluno">
                    <v-btn v-if="projeto.alunoInscrito" color="red-darken-2" variant="text" size="small" @click.stop="sairDoProjeto(projeto)">Cancelar Inscrição</v-btn>
                    <v-tooltip v-else location="top">
                      <template v-slot:activator="{ props }">
                        <div v-bind="props">
                          <v-btn
                            :disabled="projeto.status === 'Esgotado' || alunoJaInscritoNoEvento(projeto.id_evento) || projeto.statusInscricao !== 'ABERTO'"
                            color="green-darken-3" variant="flat" size="small" @click.stop="inscreverNoProjeto(projeto)"
                          >Inscrever-se</v-btn>
                        </div>
                      </template>
                      <span v-if="projeto.status === 'Esgotado'">Vagas esgotadas</span>
                      <span v-else-if="alunoJaInscritoNoEvento(projeto.id_evento)">Você já está em um projeto deste evento</span>
                      <span v-else>{{ projeto.mensagemInscricao }}</span>
                    </v-tooltip>
                  </template>
                  <template v-if="isProfessor">
                    <v-btn variant="text" color="green-darken-2" size="small" @click.stop="gerenciarProjeto(projeto.id)">
                      Gerenciar<v-icon end>mdi-arrow-right</v-icon>
                    </v-btn>
                  </template>
                </template>
              </ProjectCard>
            </v-col>
          </v-row>

          <v-table v-else-if="viewMode === 'list'" hover>
            <thead>
              <tr>
                <th class="text-left">Projeto</th>
                <th class="text-left d-none d-sm-table-cell">Orientador</th>
                <th class="text-left d-none d-md-table-cell">Inscrições</th>
                <th class="text-left">Status</th>
                <th class="text-right">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="projeto in grupo.projetos" :key="`list-${projeto.id}`" @click="verDetalhes(projeto.id)" style="cursor: pointer;">
                <td>
                  <div class="font-weight-bold">{{ projeto.titulo }}</div>
                </td>
                <td class="d-none d-sm-table-cell">{{ projeto.orientador?.nome || 'Não definido' }}</td>
                <td class="d-none d-md-table-cell">
                  <v-icon size="small" class="mr-1">mdi-account-group-outline</v-icon>
                  {{ projeto.inscritos }} / {{ projeto.maxAlunos }}
                </td>
                <td>
                    <v-chip :color="statusMap[projeto.status]?.color || 'grey'" size="small" label variant="tonal">
                        {{ projeto.status }}
                    </v-chip>
                </td>
                <td class="text-right">
                  <div class="d-flex justify-end">
                    <template v-if="isAluno">
                      <v-btn v-if="projeto.alunoInscrito" color="red-darken-2" variant="text" size="small" @click.stop="sairDoProjeto(projeto)">Cancelar</v-btn>
                      <v-tooltip v-else location="top" :text="alunoJaInscritoNoEvento(projeto.id_evento) ? 'Já inscrito neste evento' : (projeto.status === 'Esgotado' ? 'Vagas esgotadas' : projeto.mensagemInscricao)">
                        <template v-slot:activator="{ props }">
                          <div v-bind="props">
                            <v-btn :disabled="projeto.status === 'Esgotado' || alunoJaInscritoNoEvento(projeto.id_evento) || projeto.statusInscricao !== 'ABERTO'" color="green-darken-3" variant="tonal" size="small" @click.stop="inscreverNoProjeto(projeto)">Inscrever-se</v-btn>
                          </div>
                        </template>
                      </v-tooltip>
                    </template>
                    <template v-if="isProfessor">
                      <v-btn variant="text" color="green-darken-2" size="small" @click.stop="gerenciarProjeto(projeto.id)">Gerenciar</v-btn>
                    </template>
                  </div>
                </td>
              </tr>
            </tbody>
          </v-table>
        </v-card-text>
      </v-card>
    </div>

    <v-dialog v-model="showConfirmDialog" max-width="500px">
      <v-card title="Cancelar Inscrição">
        <v-card-text>Tem certeza que deseja sair da equipe do projeto "{{ selectedProject?.titulo }}"?</v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="showConfirmDialog = false">Voltar</v-btn>
          <v-btn color="red-darken-1" variant="flat" @click="confirmSairDoProjeto">Confirmar Saída</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <CrudModal 
        v-if="isProfessor"
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
/* Adicione estilos se necessário */
</style>


<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../assets/plugins/axios.js'
import { useNotificationStore } from '@/stores/notification'
import { useEventoStore } from '@/stores/eventoStore'
import { useAuthStore } from '@/stores/authStore'
const authStore = useAuthStore()

const route = useRoute()
const router = useRouter()
const eventoStore = useEventoStore()
const notificationStore = useNotificationStore()

// --- Estado do Componente ---
const project = ref(null)
const tasks = ref([])
const avaliacoes = ref([])
const membros = ref([])
const loading = ref(true)
const error = ref(null)
const activeTab = ref('detalhes')

// --- ESTADOS PARA O MODAL DE FEEDBACK DA TAREFA ---
const isTaskFeedbackModalOpen = ref(false)
const selectedTaskForFeedback = ref(null)
const isFeedbackLoading = ref(false)
const feedbackError = ref(null)

// --- ESTADOS PARA O MODAL DE CRIAR/EDITAR TAREFA ---
const isTaskModalOpen = ref(false)
const isTaskModalLoading = ref(false)
const currentTask = ref(null)
const taskFormData = ref({
  descricao: '',
  detalhe: '',
  data_inicio_prevista: null,
  data_fim_prevista: null,
  data_conclusao: null,
  id_usuario_atribuido: null,
})

// --- ESTADOS PARA O MODAL DE APAGAR TAREFA ---
const isDeleteTaskDialogOpen = ref(false)
const taskToDelete = ref(null)
// --- NOVO: ESTADOS PARA O MODAL DE SUBMISSÃO DE TAREFA ---
const isSubmitTaskModalOpen = ref(false)
const isSubmitTaskLoading = ref(false)
const taskToSubmit = ref(null)
const submissionData = ref({
  resultado: '', // Comentário da entrega
  arquivo: null, // Arquivo da entrega
})

// --- Mapas de Status ---
const statusMap = {
  1: { text: 'Em Elaboração', color: 'white-darken-2', icon: 'mdi-pencil-ruler' },
  2: { text: 'Aprovado', color: 'green-darken-2', icon: 'mdi-check-decagram' },
  3: { text: 'Reprovado', color: 'red-darken-2', icon: 'mdi-close-octagon' },
  4: { text: 'Com Ressalvas', color: 'orange-darken-2', icon: 'mdi-alert-circle-outline' },
}
const projectStatus = computed(() => statusMap[project.value?.id_situacao] || {})

const avaliacaoStatusMap = {
  2: { text: 'Aprovado', color: 'green', icon: 'mdi-check-circle' },
  3: { text: 'Reprovado', color: 'red', icon: 'mdi-close-circle' },
  4: { text: 'Reprovado com Ressalvas', color: 'orange', icon: 'mdi-alert-circle' },
}

// --- Config Kanban ---
const kanbanColumns = [
  { title: 'A Fazer', status: 1, color: 'grey' },
  { title: 'Em Andamento', status: 2, color: 'blue' },
  { title: 'Concluído', status: 3, color: 'green' },
]

const taskModalTitle = computed(() => {
  return currentTask.value ? 'Editar Tarefa' : 'Nova Tarefa'
})

// --- COMPUTED PARA JUNTAR TODOS OS FEEDBACKS (AVALIAÇÃO + TAREFAS) ---
const combinedFeedbacks = computed(() => {
  const evaluationFeedbacks = (avaliacoes.value || []).map((ava) => ({
    id: `ava-${ava.id_projeto_avaliacao}`,
    date: new Date(ava.created_at),
    type: 'Avaliação do Projeto',
    title: avaliacaoStatusMap[ava.id_situacao]?.text || 'Avaliação',
    feedbackText: ava.feedback || 'Nenhum comentário adicional.',
    author: ava.avaliador?.nome || 'Avaliador desconhecido',
    color: avaliacaoStatusMap[ava.id_situacao]?.color || 'grey',
    icon: avaliacaoStatusMap[ava.id_situacao]?.icon || 'mdi-comment-question-outline',
  }))

  const taskFeedbacks = (tasks.value || []).flatMap((task) =>
    (task.feedbacks || []).map((fb) => ({
      id: `task-${fb.id_feedback}`,
      date: new Date(fb.created_at),
      type: 'Feedback de Tarefa',
      title: `Na tarefa: "${task.descricao}"`,
      feedbackText: fb.feedback,
      author: fb.usuario?.nome || 'Usuário desconhecido',
      color: 'blue-darken-1',
      icon: 'mdi-comment-processing-outline',
    })),
  )

  return [...evaluationFeedbacks, ...taskFeedbacks].sort((a, b) => b.date - a.date)
})

// --- Lógica de busca de dados (onMounted) ---
onMounted(async () => {
  const projectId = route.params.id
  loading.value = true
  error.value = null
  try {
    const [projectResponse, tasksResponse, avaliacoesResponse, membrosResponse] = await Promise.all(
      [
        api.get(`/projetos/${projectId}`),
        api.get(`/projetos/${projectId}/tarefas`),
        api.get(`/projetos/${projectId}/avaliacoes`),
        api.get(`/membros_projeto/${projectId}`),
      ],
    )

    project.value = projectResponse.data.data || projectResponse.data
    const initialTasks = tasksResponse.data.data || tasksResponse.data || []
    avaliacoes.value = avaliacoesResponse.data.data || avaliacoesResponse.data || []
    membros.value = membrosResponse.data.data || membrosResponse.data || []

    if (Array.isArray(initialTasks) && initialTasks.length > 0) {
      // Para cada tarefa, busca seus detalhes (registros de atribuição e feedbacks) em paralelo
      const taskDetailPromises = initialTasks.map((task) => {
        const registrationsPromise = api
          .get(`/registros_tarefas?id_tarefa=${task.id_tarefa}`)
          .catch(() => ({ data: [] }))
        const feedbacksPromise = api
          .get(`/tarefas/${task.id_tarefa}/feedbacks`)
          .catch(() => ({ data: [] }))
        return Promise.all([registrationsPromise, feedbacksPromise])
      })

      const allTaskDetails = await Promise.all(taskDetailPromises)

      // Associa os detalhes buscados de volta a cada tarefa
      initialTasks.forEach((task, index) => {
        const [registrationResponse, feedbackResponse] = allTaskDetails[index]

        // Processa os registros para encontrar o responsável
        const registrations = registrationResponse.data.data || registrationResponse.data || []
        if (registrations.length > 0) {
          task.id_usuario_atribuido = registrations[registrations.length - 1].id_responsavel
        }

        // Associa os feedbacks
        task.feedbacks = feedbackResponse.data.data || feedbackResponse.data || []
      })
    }

    tasks.value = initialTasks
  } catch (err) {
    console.error('Erro ao buscar detalhes do projeto:', err)
    error.value = 'Não foi possível carregar os detalhes do projeto.'
  } finally {
    loading.value = false
  }
})

// --- FUNÇÕES DE NAVEGAÇÃO E AUXILIARES ---
const getMemberName = (userId) => {
  if (!userId || !membros.value || membros.value.length === 0) return 'Não atribuído'
  const membro = membros.value.find((m) => m.id_usuario === userId)
  return membro ? membro.usuario.nome : 'Não atribuído'
}

// --- FUNÇÕES PARA MODAIS ---
const openTaskFeedbackModal = async (task) => {
  selectedTaskForFeedback.value = { ...task, feedbacks: [] }
  isTaskFeedbackModalOpen.value = true
  isFeedbackLoading.value = true
  feedbackError.value = null

  try {
    const url = `/tarefas/${task.id_tarefa}/feedbacks`
    const { data } = await api.get(url)
    if (selectedTaskForFeedback.value) {
      selectedTaskForFeedback.value.feedbacks = data
    }
  } catch (err) {
    console.error('Erro ao buscar feedbacks da tarefa:', err)
    feedbackError.value = 'Não foi possível carregar os feedbacks. Tente novamente.'
  } finally {
    isFeedbackLoading.value = false
  }
}

const openCreateTaskModal = () => {
  currentTask.value = null
  taskFormData.value = {
    descricao: '',
    detalhe: '',
    data_inicio_prevista: null,
    data_fim_prevista: null,
    data_conclusao: null,
    id_usuario_atribuido: null,
  }
  isTaskModalOpen.value = true
}
const openEditTaskModal = (task) => {
  currentTask.value = task
  taskFormData.value = { ...task }
  isTaskModalOpen.value = true
}
const handleSaveTask = async () => {
  isTaskModalLoading.value = true
  try {
    let savedTaskData
    const { id_usuario_atribuido, ...coreTaskData } = taskFormData.value

    if (currentTask.value) {
      const { data } = await api.put(`/tarefas/${currentTask.value.id_tarefa}`, coreTaskData)
      savedTaskData = data
    } else {
      const payload = {
        id_projeto: project.value.id_projeto,
        id_situacao: 1, // 'A Fazer'
        ...coreTaskData,
      }
      const { data } = await api.post('/tarefas', payload)
      savedTaskData = data
    }

    if (id_usuario_atribuido) {
      await api.post('/registros_tarefas', {
        id_tarefa: savedTaskData.id_tarefa,
        id_responsavel: id_usuario_atribuido,
        descricao_atividade: `Tarefa atribuída ao responsável.`,
        resultado: null,
        data_execucao: new Date().toISOString().split('T')[0],
        arquivo: null,
      })
    }

    savedTaskData.id_usuario_atribuido = id_usuario_atribuido

    if (currentTask.value) {
      const index = tasks.value.findIndex((t) => t.id_tarefa === savedTaskData.id_tarefa)
      if (index !== -1) tasks.value[index] = { ...tasks.value[index], ...savedTaskData }
    } else {
      tasks.value.push(savedTaskData)
    }

    notificationStore.showSuccess('Tarefa salva com sucesso!')
    isTaskModalOpen.value = false
  } catch (err) {
    console.error('Erro ao salvar a tarefa:', err)
    notificationStore.showError('Não foi possível salvar a tarefa.')
  } finally {
    isTaskModalLoading.value = false
  }
}

const openDeleteTaskModal = (task) => {
  taskToDelete.value = task
  isDeleteTaskDialogOpen.value = true
}

const confirmDeleteTask = async () => {
  if (!taskToDelete.value) return
  notificationStore.showInfo('Apagando tarefa...')
  try {
    await api.delete(`/tarefas/${taskToDelete.value.id_tarefa}`)
    tasks.value = tasks.value.filter((t) => t.id_tarefa !== taskToDelete.value.id_tarefa)
    notificationStore.showSuccess('Tarefa apagada com sucesso!')
  } catch (err) {
    console.error('Erro ao apagar tarefa:', err)
    notificationStore.showError('Não foi possível apagar a tarefa.')
  } finally {
    isDeleteTaskDialogOpen.value = false
    taskToDelete.value = null
  }
}

// --- NOVO: FUNÇÕES PARA O MODAL DE SUBMISSÃO DE TAREFA ---
const openSubmitTaskModal = (task) => {
  taskToSubmit.value = task
  submissionData.value = { resultado: '', arquivo: null } // Reseta o formulário
  isSubmitTaskModalOpen.value = true
}

const handleSubmissionFileChange = (file) => {

  submissionData.value.arquivo = file || null;

  console.log('Arquivo capturado pela função:', submissionData.value.arquivo);
};

const handleSubmitTask = async () => {
  if (!taskToSubmit.value) return
  isSubmitTaskLoading.value = true

  // Usa FormData para enviar texto e arquivo juntos
  const formData = new FormData()
  formData.append('id_tarefa', taskToSubmit.value.id_tarefa)
  formData.append('id_responsavel', authStore.user.id_usuario) // Pega o ID do usuário logado
  formData.append('resultado', submissionData.value.resultado || 'Tarefa concluída.')
  formData.append('data_execucao', new Date().toISOString().split('T')[0])

  // Anexa o arquivo apenas se ele foi selecionado
  if (submissionData.value.arquivo) {
    formData.append('arquivo', submissionData.value.arquivo)
  

  try {
    // 1. Envia o registro da tarefa (a entrega)
    await api.post('/registros_tarefas', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    // 2. Atualiza o status da tarefa para "Concluído"
    await api.put(`/tarefas/${taskToSubmit.value.id_tarefa}`, { id_situacao: 3 })

    // 3. Atualiza a interface, movendo o card
    const task = tasks.value.find((t) => t.id_tarefa === taskToSubmit.value.id_tarefa)
    if (task) {
      task.id_situacao = 3
    }

    notificationStore.showSuccess('Tarefa entregue com sucesso!')
    isSubmitTaskModalOpen.value = false
  } catch (err) {
    console.error('Erro ao submeter a tarefa:', err)
    notificationStore.showError('Não foi possível entregar a tarefa.')
  } finally {
    isSubmitTaskLoading.value = false
  }
  }
}

// --- Funções do Kanban (Drag and Drop) ---
const handleDragStart = (event, task) => {
  event.dataTransfer.setData('text/plain', task.id_tarefa)
  event.dataTransfer.dropEffect = 'move'
}
const handleDrop = async (event, newStatus) => {
  const taskId = event.dataTransfer.getData('text/plain')
  const task = tasks.value.find((t) => t.id_tarefa == taskId)
  if (task && task.id_situacao !== newStatus) {
    const originalStatus = task.id_situacao
    task.id_situacao = newStatus // Otimista
    try {
      await api.put(`/tarefas/${taskId}`, { id_situacao: newStatus })
    } catch (err) {
      task.id_situacao = originalStatus // Reverte
    }
  }
}
const filterTasksByStatus = (status) => {
  return tasks.value.filter((task) => task.id_situacao === status)
}

// --- Funções para formatação de data ---
const formatDate = (dateString) => {
  if (!dateString) return 'Data indefinida'
  return new Date(dateString).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const formatDateSimple = (dateString) => {
  if (!dateString) return null
  const date = new Date(dateString)
  const userTimezoneOffset = date.getTimezoneOffset() * 60000
  return new Date(date.getTime() - userTimezoneOffset).toISOString().split('T')[0]
}
</script>

<template>
  <v-container fluid>
    <v-btn variant="text" prepend-icon="mdi-arrow-left" @click="router.go(-1)" class="mb-6">
      Voltar
    </v-btn>

    <div v-if="loading" class="text-center py-16">
      <v-progress-circular indeterminate color="green-darken-3" size="64"></v-progress-circular>
      <p class="mt-4 text-grey-darken-1">A carregar projeto...</p>
    </div>
    <v-alert v-else-if="error" type="error" variant="tonal">{{ error }}</v-alert>

    <div v-else-if="project">
      <v-card theme="dark" class="mb-8 bg-green-darken-4">
        <v-card-item class="pa-4 pa-sm-6">
          <div class="d-flex flex-wrap justify-space-between align-center">
            <div>
              <p class="text-overline">Projeto</p>
              <h1 class="text-h4 font-weight-bold">{{ project.titulo }}</h1>
            </div>
            <v-chip
              :color="projectStatus.color"
              :prepend-icon="projectStatus.icon"
              variant="tonal"
              label
            >
              {{ projectStatus.text }}
            </v-chip>
          </div>
        </v-card-item>
      </v-card>

      <v-card>
        <v-tabs v-model="activeTab" bg-color="green-darken-3" color="white" grow>
          <v-tab value="detalhes"
            ><v-icon start>mdi-text-box-search-outline</v-icon> Detalhes</v-tab
          >
          <v-tab value="feedback"
            ><v-icon start>mdi-comment-quote-outline</v-icon> Histórico de Feedbacks</v-tab
          >
          <v-tab value="equipe"><v-icon start>mdi-account-group-outline</v-icon> Equipe</v-tab>
          <v-tab value="tarefas" :disabled="project.id_situacao !== 2"
            ><v-icon start>mdi-view-dashboard-outline</v-icon> Tarefas</v-tab
          >
        </v-tabs>

        <v-window v-model="activeTab">
          <v-window-item value="detalhes">
            <v-card-text class="pa-4 pa-md-6">
              <v-list lines="two" bg-color="transparent">
                <v-list-item
                  prepend-icon="mdi-lightbulb-on-outline"
                  title="Problema a ser Resolvido"
                  :subtitle="project.problema"
                  class="mb-4"
                ></v-list-item>
                <v-list-item
                  prepend-icon="mdi-bullseye-arrow"
                  title="Relevância e Justificativa"
                  :subtitle="project.relevancia"
                  class="mb-4"
                ></v-list-item>
              </v-list>
            </v-card-text>
          </v-window-item>

          <v-window-item value="feedback">
            <v-card-text class="pa-4 pa-md-6">
              <div
                v-if="combinedFeedbacks.length === 0"
                class="text-center pa-8 text-grey-darken-1"
              >
                <v-icon size="48" class="mb-4">mdi-comment-processing-outline</v-icon>
                <p>Nenhum feedback foi registrado para este projeto ainda.</p>
              </div>
              <v-timeline v-else side="end" align="start">
                <v-timeline-item
                  v-for="fb in combinedFeedbacks"
                  :key="fb.id"
                  :dot-color="fb.color"
                  :icon="fb.icon"
                  size="small"
                >
                  <template v-slot:opposite>
                    <div class="text-caption text-grey-darken-1">{{ formatDate(fb.date) }}</div>
                  </template>
                  <div class="feedback-item">
                    <div class="font-weight-bold">{{ fb.title }}</div>
                    <p class="text-body-2 mt-2 font-italic">"{{ fb.feedbackText }}"</p>
                    <div class="text-caption opacity-75 mt-3">Por: {{ fb.author }}</div>
                  </div>
                </v-timeline-item>
              </v-timeline>
            </v-card-text>
          </v-window-item>

          <v-window-item value="equipe">
            <div v-if="membros.length === 0" class="text-center pa-8 text-grey-darken-1">
              <v-icon size="48" class="mb-4">mdi-account-group-outline</v-icon>
              <p>Nenhum membro foi registrado para este projeto ainda.</p>
            </div>
            <v-card-text v-else class="pa-0">
              <v-list lines="two">
                <v-list-subheader>Membros da Equipe</v-list-subheader>
                <v-list-item
                  v-for="membro in membros"
                  :key="membro.id_usuario"
                  :title="membro.usuario.nome"
                  :subtitle="membro.usuario.email"
                >
                  <template v-slot:prepend>
                    <v-avatar color="green-darken-4">
                      <span class="text-h6">{{ membro.usuario.nome.charAt(0) }}</span>
                    </v-avatar>
                  </template>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-window-item>

          <v-window-item value="tarefas">
            <v-card-title class="d-flex justify-space-between align-center">
              <span>Quadro de Tarefas</span>
              <v-btn
                color="green"
                variant="flat"
                @click="openCreateTaskModal"
                prepend-icon="mdi-plus"
              >
                Nova Tarefa
              </v-btn>
            </v-card-title>
            <v-card-text class="bg-grey-lighten-4">
              <v-row>
                <v-col
                  v-for="column in kanbanColumns"
                  :key="column.status"
                  cols="12"
                  md="4"
                  @dragover.prevent
                  @drop="handleDrop($event, column.status)"
                >
                  <div class="pa-4 rounded-lg fill-height" :class="`bg-${column.color}-lighten-5`">
                    <div class="d-flex align-center mb-4">
                      <v-icon :color="column.color" class="mr-2">mdi-circle-medium</v-icon>
                      <span class="font-weight-bold text-grey-darken-3">{{ column.title }}</span>
                      <v-chip size="small" :color="column.color" class="ml-2">{{
                        filterTasksByStatus(column.status).length
                      }}</v-chip>
                    </div>
                    <v-card
                      v-for="task in filterTasksByStatus(column.status)"
                      :key="task.id_tarefa"
                      class="mb-3 task-card"
                      theme="light"
                      variant="flat"
                      draggable="true"
                      @dragstart="handleDragStart($event, task)"
                    >
                      <v-card-text class="font-weight-medium text-grey-darken-4 pb-1">
                        {{ task.descricao }}
                        <p
                          v-if="task.detalhe"
                          class="text-caption font-weight-regular text-grey-darken-1 mt-1"
                        >
                          {{ task.detalhe }}
                        </p>
                        <div
                          v-if="task.data_inicio_prevista || task.data_fim_prevista"
                          class="mt-3 d-flex flex-wrap ga-2"
                        >
                          <v-chip
                            v-if="task.data_inicio_prevista"
                            size="x-small"
                            color="blue-grey"
                            variant="tonal"
                          >
                            <v-icon start icon="mdi-calendar-arrow-right"></v-icon>
                            {{ formatDateSimple(task.data_inicio_prevista) }}
                            <v-tooltip activator="parent" location="top">Início Previsto</v-tooltip>
                          </v-chip>
                          <v-chip
                            v-if="task.data_fim_prevista"
                            size="x-small"
                            color="blue-grey"
                            variant="tonal"
                          >
                            <v-icon start icon="mdi-calendar-arrow-left"></v-icon>
                            {{ formatDateSimple(task.data_fim_prevista) }}
                            <v-tooltip activator="parent" location="top">Fim Previsto</v-tooltip>
                          </v-chip>
                        </div>
                      </v-card-text>
                      <v-card-actions class="pt-0 px-4 pb-2">
                        <v-chip
                          v-if="task.id_usuario_atribuido"
                          size="small"
                          color="green"
                          variant="tonal"
                          label
                        >
                          <v-icon start icon="mdi-account-outline"></v-icon>
                          {{ getMemberName(task.id_usuario_atribuido) }}
                        </v-chip>
                        <v-spacer></v-spacer>
                        <v-btn
                          icon="mdi-pencil"
                          variant="text"
                          size="x-small"
                          @click.stop="openEditTaskModal(task)"
                        ></v-btn>
                        <v-btn
                          icon="mdi-delete-outline"
                          color="red"
                          variant="text"
                          size="x-small"
                          @click.stop="openDeleteTaskModal(task)"
                        ></v-btn>

                        <v-btn
                          color="primary"
                          variant="text"
                          size="small"
                          @click.stop="openTaskFeedbackModal(task)"
                          >Feedbacks</v-btn
                        >
                        <v-btn
                          v-if="task.id_situacao !== 3"
                          color="green"
                          variant="text"
                          size="small"
                          @click.stop="openSubmitTaskModal(task)"
                        >
                          Entregar
                        </v-btn>
                      </v-card-actions>
                    </v-card>
                  </div>
                </v-col>
              </v-row>
            </v-card-text>
          </v-window-item>
        </v-window>
      </v-card>
    </div>

    <!-- MODAL CRIAR/EDITAR TAREFA -->
    <v-dialog v-model="isTaskModalOpen" persistent max-width="700px">
      <v-card>
        <v-card-title class="d-flex align-center text-h5 bg-green-darken-3 text-white">
          {{ taskModalTitle }}
          <v-spacer></v-spacer>
          <v-btn icon="mdi-close" variant="text" @click="isTaskModalOpen = false"></v-btn>
        </v-card-title>
        <v-card-text class="pt-6">
          <v-form>
            <v-text-field
              v-model="taskFormData.descricao"
              label="Título da Tarefa"
              variant="outlined"
              :rules="[(v) => !!v || 'O título é obrigatório']"
              autofocus
            ></v-text-field>
            <v-textarea
              v-model="taskFormData.detalhe"
              label="Descrição Detalhada (Opcional)"
              variant="outlined"
              rows="3"
              class="mt-4"
            ></v-textarea>

            <v-select
              v-model="taskFormData.id_usuario_atribuido"
              :items="membros"
              item-title="usuario.nome"
              item-value="id_usuario"
              label="Atribuir a (Opcional)"
              variant="outlined"
              class="mt-4"
              clearable
            ></v-select>

            <v-row class="mt-2">
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="taskFormData.data_inicio_prevista"
                  label="Início Previsto"
                  type="date"
                  variant="outlined"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="taskFormData.data_fim_prevista"
                  label="Fim Previsto"
                  type="date"
                  variant="outlined"
                ></v-text-field>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn
            color="grey-darken-1"
            variant="text"
            @click="isTaskModalOpen = false"
            :disabled="isTaskModalLoading"
            >Cancelar</v-btn
          >
          <v-btn
            color="green-darken-2"
            variant="flat"
            @click="handleSaveTask"
            :loading="isTaskModalLoading"
            >Salvar</v-btn
          >
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="isTaskFeedbackModalOpen" max-width="700px" persistent>
      <v-card>
        <v-card-title class="text-h5 bg-green-darken-3 text-white">
          <v-icon start>mdi-comment-multiple-outline</v-icon>
          Feedbacks da Tarefa
        </v-card-title>
        <v-card-subtitle class="bg-green-darken-3 text-white pb-2">
          "{{ selectedTaskForFeedback?.descricao }}"
        </v-card-subtitle>
        <v-card-text class="pt-6">
          <div v-if="isFeedbackLoading" class="text-center py-8">
            <v-progress-circular indeterminate color="green-darken-2"></v-progress-circular>
            <p class="mt-3 text-grey-darken-1">Buscando feedbacks...</p>
          </div>
          <v-alert v-else-if="feedbackError" type="error" variant="tonal">
            {{ feedbackError }}
          </v-alert>

          <div v-else>
            <div
              v-if="
                !selectedTaskForFeedback?.feedbacks ||
                selectedTaskForFeedback.feedbacks.length === 0
              "
              class="text-center pa-8 text-grey-darken-1"
            >
              <v-icon size="48" class="mb-4">mdi-comment-remove-outline</v-icon>
              <p>Nenhum feedback registrado para esta tarefa específica.</p>
            </div>
            <v-timeline v-else side="end" align="start" density="compact">
              <v-timeline-item
                v-for="fb in selectedTaskForFeedback.feedbacks"
                :key="fb.id_feedback"
                dot-color="green-darken-1"
                size="small"
              >
                <div class="feedback-item">
                  <p class="text-body-1 font-italic">"{{ fb.feedback }}"</p>
                  <div class="text-caption text-grey-darken-1 mt-2">
                    - {{ fb.usuario?.nome || 'Usuário' }} em {{ formatDate(fb.created_at) }}
                  </div>
                </div>
              </v-timeline-item>
            </v-timeline>
          </div>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn color="grey-darken-1" variant="text" @click="isTaskFeedbackModalOpen = false"
            >Fechar</v-btn
          >
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- DIÁLOGO DE CONFIRMAÇÃO PARA APAGAR TAREFA -->
    <v-dialog v-model="isDeleteTaskDialogOpen" persistent max-width="500px">
      <v-card>
        <v-card-title class="text-h5">
          <v-icon color="red" start>mdi-alert-circle-outline</v-icon>
          Confirmar Exclusão
        </v-card-title>
        <v-card-text>
          Você tem certeza que deseja apagar a tarefa
          <strong>"{{ taskToDelete?.descricao }}"</strong>? <br /><br />
          Esta ação não pode ser desfeita.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey-darken-1" variant="text" @click="isDeleteTaskDialogOpen = false">
            Cancelar
          </v-btn>
          <v-btn color="red-darken-1" variant="flat" @click="confirmDeleteTask">
            Apagar Tarefa
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="isSubmitTaskModalOpen" persistent max-width="700px">
      <v-card>
        <v-card-title class="d-flex align-center text-h5 bg-green-darken-3 text-white">
          <v-icon start>mdi-check-circle-outline</v-icon>
          Entregar Tarefa
          <v-spacer></v-spacer>
          <v-btn icon="mdi-close" variant="text" @click="isSubmitTaskModalOpen = false"></v-btn>
        </v-card-title>
        <v-card-subtitle class="bg-green-darken-3 text-white pb-2">
          "{{ taskToSubmit?.descricao }}"
        </v-card-subtitle>
        <v-card-text class="pt-6">
          <v-form @submit.prevent="handleSubmitTask">
            <v-textarea
              v-model="submissionData.resultado"
              label="Comentário da Entrega (Opcional)"
              placeholder="Descreva o que foi feito ou deixe um comentário para o orientador."
              variant="outlined"
              rows="4"
              autofocus
            ></v-textarea>
            <v-file-input
              @update:modelValue="handleSubmissionFileChange"
              label="Anexar Arquivo (Opcional)"
              variant="outlined"
              prepend-icon="mdi-paperclip"
              accept="image/*,application/jpg,jpeg,png,pdf,doc,docx,xls,xlsx,ppt,pptx,txt"
              class="mt-4"
              clearable
            ></v-file-input>
          </v-form>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn
            color="grey-darken-1"
            variant="text"
            @click="isSubmitTaskModalOpen = false"
            :disabled="isSubmitTaskLoading"
            >Cancelar</v-btn
          >
          <v-btn
            color="green-darken-2"
            variant="flat"
            @click="handleSubmitTask"
            :loading="isSubmitTaskLoading"
          >
            Confirmar Entrega
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<style scoped>
.fill-height {
  min-height: 400px;
}
.task-card {
  cursor: move;
  transition: box-shadow 0.2s ease-in-out;
}
.task-card:hover {
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
.feedback-item {
  border-left: 3px solid #e0e0e0;
  padding-left: 16px;
}
</style>

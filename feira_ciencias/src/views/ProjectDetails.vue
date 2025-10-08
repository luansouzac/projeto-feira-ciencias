<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/assets/plugins/axios.js'
import { useNotificationStore } from '@/stores/notification'
import { useAuthStore } from '@/stores/authStore'

// Importação dos novos componentes de abas e modais
import TeamManagementTab from '@/components/project/TeamManagementTab.vue'
import KanbanBoardTab from '@/components/project/KanbanBoardTab.vue'
import FeedbackTimelineTab from '@/components/project/FeedbackTimelineTab.vue'
import ProjectDetailsTab from '@/components/project/ProjectDetailsTab.vue'
import TaskFormModal from '@/components/modals/TaskFormModal.vue'
import AddMemberModal from '@/components/modals/AddMemberModal.vue'
import TaskFeedbackModal from '@/components/modals/TaskFeedbackModal.vue'
import ConfirmDeleteDialog from '@/components/dialogs/ConfirmDeleteDialog.vue'
import SubmitTaskModal from '@/components/modals/SubmitTaskModal.vue'

// --- Instâncias e Stores ---
const authStore = useAuthStore()
const route = useRoute()
const router = useRouter()
const notificationStore = useNotificationStore()

// --- ESTADO PRINCIPAL DA PÁGINA ---
const project = ref(null)
const tasks = ref([])
const avaliacoes = ref([])
const membros = ref([])
const loading = ref(true)
const error = ref(null)
const activeTab = ref('detalhes')

// --- ESTADO PARA CONTROLE DOS MODAIS (A LÓGICA INTERNA FICA NOS FILHOS) ---
const isTaskModalOpen = ref(false)
const isTaskModalLoading = ref(false)
const currentTask = ref(null) // Usado para saber qual tarefa editar

const isAddMemberModalOpen = ref(false)

const isTaskFeedbackModalOpen = ref(false)
const selectedTaskForFeedback = ref(null)

const isDeleteTaskDialogOpen = ref(false)
const taskToDelete = ref(null)

const isSubmitTaskModalOpen = ref(false)
const taskToSubmit = ref(null)

const isSubmitTaskLoading = ref(false)

// --- PROPRIEDADES COMPUTADAS PRINCIPAIS ---

const projectStatus = computed(() => {
  const statusMap = {
    1: { text: 'Em Elaboração', color: 'white-darken-2', icon: 'mdi-pencil-ruler' },
    2: { text: 'Aprovado', color: 'green-darken-2', icon: 'mdi-check-decagram' },
    3: { text: 'Reprovado', color: 'red-darken-2', icon: 'mdi-close-octagon' },
    4: { text: 'Com Ressalvas', color: 'orange-darken-2', icon: 'mdi-alert-circle-outline' },
  }
  return statusMap[project.value?.id_situacao] || {}
})

const canCreateTasks = computed(() => {
  if (!authStore.user || !project.value) return false
  const isResponsavel = authStore.user.id_usuario === project.value.id_responsavel
  const isMembroDaEquipe = membros.value.some((m) => m.id_usuario === authStore.user.id_usuario)
  return isResponsavel || isMembroDaEquipe
})

const isTeamFull = computed(() => {
  if (!project.value) return false
  const maxMembers = project.value.max_pessoas ?? 0
  if (maxMembers === 0) return false
  return membros.value.length >= maxMembers
})

const isProfessor = computed(() => authStore.user?.id_tipo_usuario === 4)


const combinedFeedbacks = computed(() => {
  const createAuthorObject = (userObject) => {
    if (!userObject) return { name: 'Usuário desconhecido', photo: null }
    return {
      name: userObject.nome,
      photo: userObject.photo, 
    }
  }

  const findAuthorObject = (userId) => {
    if (!userId || !membros.value || membros.value.length === 0) {
      return { name: 'Não atribuído', photo: null }
    }
    const membro = membros.value.find((m) => m.id_usuario === userId)
    return membro ? createAuthorObject(membro.usuario) : { name: 'Usuário desconhecido', photo: null }
  }
  
  const avaliacaoStatusMap = {
    2: { text: 'Aprovado', color: 'green', icon: 'mdi-check-circle' },
    3: { text: 'Reprovado', color: 'red', icon: 'mdi-close-circle' },
    4: { text: 'Reprovado com Ressalvas', color: 'orange', icon: 'mdi-alert-circle' },
  }

  const evaluationFeedbacks = (avaliacoes.value || []).map((ava) => ({
    id: `ava-${ava.id_projeto_avaliacao}`,
    date: new Date(ava.created_at),
    type: 'Avaliação do Projeto',
    title: avaliacaoStatusMap[ava.id_situacao]?.text || 'Avaliação',
    feedbackText: ava.feedback || 'Nenhum comentário adicional.',
    author: createAuthorObject(ava.avaliador), // Usa a função padronizada
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
      author: createAuthorObject(fb.usuario), // Usa a função padronizada
      color: 'blue-darken-1',
      icon: 'mdi-comment-processing-outline',
    })),
  )

  const submissionFeedbacks = (tasks.value || []).flatMap((task) =>
    (task.registros || [])
      .filter((reg) => reg.resultado || reg.arquivo)
      .map((reg) => ({
        id: `sub-${reg.id_registro}`, // CORREÇÃO: Usar id_registro que é único
        date: new Date(reg.data_execucao),
        type: 'Entrega de Tarefa',
        title: `Entrega da tarefa: "${task.descricao}"`,
        feedbackText: reg.resultado || 'Tarefa entregue sem comentários.',
        author: findAuthorObject(reg.id_responsavel), // Usa a função corrigida
        color: 'purple-darken-1',
        icon: 'mdi-upload',
        arquivo: reg.arquivo,
      })),
  )
  console.log(submissionFeedbacks);
  
  return [...evaluationFeedbacks, ...taskFeedbacks, ...submissionFeedbacks];
});

// --- LÓGICA DE BUSCA DE DADOS ---
onMounted(async () => {
  const projectId = route.params.id
  loading.value = true
  error.value = null
  try {
    const [projectResponse, membrosResponse, tasksResponse, avaliacoesResponse] = await Promise.all(
      [
        api.get(`/projetos/${projectId}`),
        api.get(`/membros_projeto/${projectId}`),
        api.get(`/projetos/${projectId}/tarefas`),
        api.get(`/projetos/${projectId}/avaliacoes`),
      ],
    )

    project.value = projectResponse.data.data || projectResponse.data
    const initialTasks = tasksResponse.data.data || tasksResponse.data || []
    avaliacoes.value = avaliacoesResponse.data.data || avaliacoesResponse.data || []
    membros.value = membrosResponse.data.data || membrosResponse.data || []

    if (Array.isArray(initialTasks) && initialTasks.length > 0) {
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
      initialTasks.forEach((task, index) => {
        const [registrationResponse, feedbackResponse] = allTaskDetails[index]
        task.registros = registrationResponse.data.data || registrationResponse.data || []
        if (task.registros.length > 0) {
          task.id_usuario_atribuido = task.registros[task.registros.length - 1].id_responsavel
        }
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

// --- FUNÇÕES DE MANIPULAÇÃO DE EVENTOS (HANDLERS) ---

// -- Handlers da Equipe --
const openAddMemberModal = () => (isAddMemberModalOpen.value = true)

const handleAddMember = async (user) => {
  // --- VALIDAÇÃO DE DATA NO FRONTEND (Feedback Rápido) ---
  const agora = new Date();
  // Garanta que seu objeto 'project.evento' contenha essas datas
  const inicioInscricao = new Date(project.value.evento?.data_inicio_inscricao);
  const fimInscricao = new Date(project.value.evento?.data_fim_inscricao);

  // if (!project.value.evento || agora < inicioInscricao || agora > fimInscricao) {
  //   notificationStore.showError('As inscrições não estão abertas neste momento.');
  //   return; // Interrompe a função
  // }
  // --- FIM DA VALIDAÇÃO DE DATA ---

  notificationStore.showInfo(`Adicionando ${user.nome}...`)
  try {
    const payload = { id_usuario: user.id_usuario }
    const response = await api.post(`/projetos/${project.value.id_projeto}/inscrever`, payload)

    membros.value.push(response.data.data || response.data)
    notificationStore.showSuccess(`${user.nome} foi adicionado à equipe!`)
    isAddMemberModalOpen.value = false
  } catch (err) {
    console.error('Erro ao adicionar membro:', err)
    
    // Lida com a resposta de erro específica do backend
    const mensagemErro = err.response?.data?.message || 'Não foi possível adicionar o membro.';
    notificationStore.showError(mensagemErro);
  }
}

const handleRemoveMember = async (memberToRemove) => {
  if (!confirm(`Tem certeza que deseja remover ${memberToRemove.usuario.snome} da equipe?`)) return
  notificationStore.showInfo(`Removendo ${memberToRemove.usuario.nome}...`)
  try {
    const projetoId = project.value.id_projeto
    if (!projetoId) throw new Error('ID da equipe não encontrado no projeto.')

    const usuarioId = memberToRemove.id_usuario
    await api.post(`/projetos/desinscrever/${projetoId}/${usuarioId}`)
    membros.value = membros.value.filter((m) => m.id_membro !== memberToRemove.id_membro)
    notificationStore.showSuccess(`${memberToRemove.usuario.nome} foi removido da equipe.`)
  } catch (err) {
    console.error('Erro ao remover membro:', err)
    notificationStore.showError(err.response?.data?.message || 'Não foi possível remover o membro.')
  }
}

// -- Handlers das Tarefas --
const openCreateTaskModal = () => {
  currentTask.value = null
  isTaskModalOpen.value = true
}
const openEditTaskModal = (task) => {
  currentTask.value = task
  isTaskModalOpen.value = true
}
const handleSaveTask = async (taskDataFromModal) => {
  isTaskModalLoading.value = true
  try {
    const { id_usuario_atribuido, ...coreTaskData } = taskDataFromModal
    let savedTaskData
    if (currentTask.value) {
      const { data } = await api.put(`/tarefas/${currentTask.value.id_tarefa}`, coreTaskData)
      savedTaskData = data
    } else {
      const payload = { id_projeto: project.value.id_projeto, id_situacao: 1, ...coreTaskData }
      const { data } = await api.post('/tarefas', payload)
      savedTaskData = data
    }

    if (id_usuario_atribuido && id_usuario_atribuido !== currentTask.value?.id_usuario_atribuido) {
      await api.post('/registros_tarefas', {
        id_tarefa: savedTaskData.id_tarefa,
        id_responsavel: id_usuario_atribuido,
        descricao_atividade: `Tarefa atribuída.`,
        data_execucao: new Date().toISOString().split('T')[0],
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
  try {
    await api.delete(`/tarefas/${taskToDelete.value.id_tarefa}`)
    tasks.value = tasks.value.filter((t) => t.id_tarefa !== taskToDelete.value.id_tarefa)
    notificationStore.showSuccess('Tarefa apagada com sucesso!')
  } catch (err) {
    notificationStore.showError('Não foi possível apagar a tarefa.')
  } finally {
    isDeleteTaskDialogOpen.value = false
    taskToDelete.value = null
  }
}

const handleTaskStatusUpdate = async ({ taskId, newStatus, task }) => {
  const originalStatus = task.id_situacao
  task.id_situacao = newStatus
  try {
    await api.put(`/tarefas/${taskId}`, { id_situacao: newStatus })
  } catch (err) {
    task.id_situacao = originalStatus
    notificationStore.showError('Não foi possível mover a tarefa.')
  }
}

// -- Handlers de Feedback --
const openTaskFeedbackModal = (task) => {
  selectedTaskForFeedback.value = task
  isTaskFeedbackModalOpen.value = true
}
const handleFeedbackSent = (newFeedback) => {
  notificationStore.showSuccess('Feedback enviado com sucesso!')
  const taskInMainList = tasks.value.find((t) => t.id_tarefa === newFeedback.id_tarefa)
  if (taskInMainList) {
    if (!taskInMainList.feedbacks) taskInMainList.feedbacks = []
    taskInMainList.feedbacks.unshift(newFeedback)
  }
}

// -- Handlers de Submissão de Tarefa --
const openSubmitTaskModal = (task) => {
  taskToSubmit.value = task
  isSubmitTaskModalOpen.value = true
}

const handleSubmitTask = async (submissionDataFromModal) => {
  if (!taskToSubmit.value) return
  isSubmitTaskLoading.value = true

  try {
    const formData = new FormData()
    formData.append('id_tarefa', taskToSubmit.value.id_tarefa)
    formData.append('id_responsavel', authStore.user.id_usuario)
    formData.append('resultado', submissionDataFromModal.resultado || 'Tarefa concluída.')

    const arquivo = submissionDataFromModal.arquivo
    if (arquivo) {
      formData.append('arquivo', arquivo)
    }

    formData.append('data_execucao', new Date().toISOString().split('T')[0])

    const response = await api.post('/registros_tarefas', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    const newSubmissionRecord = response.data.data || response.data

    await api.put(`/tarefas/${taskToSubmit.value.id_tarefa}`, { id_situacao: 3 })

    const taskInMainList = tasks.value.find((t) => t.id_tarefa === taskToSubmit.value.id_tarefa)
    if (taskInMainList) {
      // Atualiza o status na interface para mover o card
      taskInMainList.id_situacao = 3

      // Adiciona o novo registro ao histórico da tarefa na interface
      if (!taskInMainList.registros) {
        taskInMainList.registros = []
      }
      taskInMainList.registros.unshift(newSubmissionRecord)
    }

    notificationStore.showSuccess('Tarefa entregue com sucesso!')
    isSubmitTaskModalOpen.value = false
  } catch (err) {
    console.error('Erro ao submeter a tarefa:', err)
    // Se qualquer uma das chamadas falhar, o erro será capturado aqui.
    notificationStore.showError('Não foi possível entregar a tarefa.')
  } finally {
    isSubmitTaskLoading.value = false
  }
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
          <v-tab value="feedback"><v-icon start>mdi-comment-quote-outline</v-icon> Histórico</v-tab>
          <v-tab value="equipe"><v-icon start>mdi-account-group-outline</v-icon> Equipe</v-tab>
          <v-tab value="tarefas" :disabled="project.id_situacao !== 2"
            ><v-icon start>mdi-view-dashboard-outline</v-icon> Tarefas</v-tab
          >
        </v-tabs>

        <v-window v-model="activeTab">
          <v-window-item value="detalhes">
            <ProjectDetailsTab :project="project" />
          </v-window-item>

          <v-window-item value="feedback">
            <FeedbackTimelineTab :feedbacks="combinedFeedbacks" />
          </v-window-item>

          <v-window-item value="equipe">
            <TeamManagementTab
              :membros="membros"
              :project="project"
              :can-create-tasks="canCreateTasks"
              :is-team-full="isTeamFull"
              :auth-user-id="authStore.user.id_usuario"
              @open-add-modal="openAddMemberModal"
              @remove-member="handleRemoveMember"
            />
          </v-window-item>

          <v-window-item value="tarefas">
            <KanbanBoardTab
              :tasks="tasks"
              :membros="membros"
              :can-create-tasks="canCreateTasks"
              @open-create-task="openCreateTaskModal"
              @open-edit-task="openEditTaskModal"
              @open-delete-task="openDeleteTaskModal"
              @open-feedback-task="openTaskFeedbackModal"
              @open-submit-task="openSubmitTaskModal"
              @update-task-status="handleTaskStatusUpdate"
            />
          </v-window-item>
        </v-window>
      </v-card>
    </div>

    <TaskFormModal
      v-model="isTaskModalOpen"
      :task-to-edit="currentTask"
      :membros="membros"
      :is-loading="isTaskModalLoading"
      @save="handleSaveTask"
    />

    <AddMemberModal
      v-model="isAddMemberModalOpen"
      :membros-atuais="membros"
      @add-member="handleAddMember"
    />

    <TaskFeedbackModal
      v-model="isTaskFeedbackModalOpen"
      :task="selectedTaskForFeedback"
      :can-interact="canCreateTasks"
      @message-sent="handleFeedbackSent"
    />

    <ConfirmDeleteDialog
      v-model="isDeleteTaskDialogOpen"
      :item-name="taskToDelete?.descricao"
      @confirm="confirmDeleteTask"
    />

    <SubmitTaskModal
      v-model="isSubmitTaskModalOpen"
      :task="taskToSubmit"
      :is-loading="isSubmitTaskLoading"
      @submit="handleSubmitTask"
    />
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
.task-card.not-draggable {
  cursor: not-allowed;
}
</style>

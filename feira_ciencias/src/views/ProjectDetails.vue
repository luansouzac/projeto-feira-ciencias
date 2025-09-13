<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../assets/plugins/axios.js';
import CrudModal from '@/components/CrudModal.vue';
import { useEventoStore } from '@/stores/eventoStore';

const route = useRoute();
const router = useRouter();
const eventoStore = useEventoStore();

// --- Estado do Componente ---
const project = ref(null);
const tasks = ref([]);
const avaliacoes = ref([]);
const membros = ref([]);
const loading = ref(true);
const error = ref(null);
const activeTab = ref('detalhes');

// --- ESTADOS PARA O MODAL DE FEEDBACK DA TAREFA ---
const isTaskFeedbackModalOpen = ref(false);
const selectedTaskForFeedback = ref(null);
const isFeedbackLoading = ref(false);
const feedbackError = ref(null);


// --- Mapas de Status ---
const statusMap = {
  1: { text: 'Em Elaboração', color: 'white-darken-2', icon: 'mdi-pencil-ruler' },
  2: { text: 'Aprovado', color: 'green-darken-2', icon: 'mdi-check-decagram' },
  3: { text: 'Reprovado', color: 'red-darken-2', icon: 'mdi-close-octagon' },
  4: { text: 'Com Ressalvas', color: 'orange-darken-2', icon: 'mdi-alert-circle-outline' },
};
const projectStatus = computed(() => statusMap[project.value?.id_situacao] || {});

const avaliacaoStatusMap = {
  2: { text: 'Aprovado', color: 'green', icon: 'mdi-check-circle' },
  3: { text: 'Reprovado', color: 'red', icon: 'mdi-close-circle' },
  4: { text: 'Reprovado com Ressalvas', color: 'orange', icon: 'mdi-alert-circle' },
};

// --- Config Kanban ---
const kanbanColumns = [
  { title: 'A Fazer', status: 1, color: 'grey' },
  { title: 'Em Andamento', status: 2, color: 'blue' },
  { title: 'Concluído', status: 3, color: 'green' },
];

// --- COMPUTED PARA JUNTAR TODOS OS FEEDBACKS (AVALIAÇÃO + TAREFAS) ---
const combinedFeedbacks = computed(() => {
  const evaluationFeedbacks = (avaliacoes.value || []).map(ava => ({
    id: `ava-${ava.id_projeto_avaliacao}`,
    date: new Date(ava.created_at),
    type: 'Avaliação do Projeto',
    title: avaliacaoStatusMap[ava.id_situacao]?.text || 'Avaliação',
    feedbackText: ava.feedback || 'Nenhum comentário adicional.',
    author: ava.avaliador?.nome || 'Avaliador desconhecido',
    color: avaliacaoStatusMap[ava.id_situacao]?.color || 'grey',
    icon: avaliacaoStatusMap[ava.id_situacao]?.icon || 'mdi-comment-question-outline'
  }));

  const taskFeedbacks = (tasks.value || [])
    .flatMap(task =>
      (task.feedbacks || []).map(fb => ({
        id: `task-${fb.id_feedback}`,
        date: new Date(fb.created_at),
        type: 'Feedback de Tarefa',
        title: `Na tarefa: "${task.descricao}"`,
        feedbackText: fb.feedback,
        author: fb.usuario?.nome || 'Usuário desconhecido',
        color: 'blue-darken-1', 
        icon: 'mdi-comment-processing-outline'
      }))
    );

  return [...evaluationFeedbacks, ...taskFeedbacks]
    .sort((a, b) => b.date - a.date);
});

// --- Lógica de busca de dados (onMounted) ---
onMounted(async () => {
  const projectId = route.params.id;
  try {
    // 1. Busca os dados primários
    const [projectResponse, tasksResponse, avaliacoesResponse, membrosResponse] = await Promise.all([
      api.get(`/projetos/${projectId}`),
      api.get(`/projetos/${projectId}/tarefas`), 
      api.get(`/projetos/${projectId}/avaliacoes`),
      api.get(`/membros_projeto/${projectId}`),
    ]);

    project.value = projectResponse.data;
    const initialTasks = tasksResponse.data;
    avaliacoes.value = avaliacoesResponse.data;
    membros.value = membrosResponse.data;

    if (initialTasks && initialTasks.length > 0) {
      const feedbackPromises = initialTasks.map(task =>
        api.get(`/tarefas/${task.id_tarefa}/feedbacks`).catch(err => {
          console.warn(`Não foi possível buscar feedbacks para a tarefa ${task.id_tarefa}:`, err);
          return { data: [] };
        })
      );
      
      const feedbackResponses = await Promise.all(feedbackPromises);
      
      initialTasks.forEach((task, index) => {
        task.feedbacks = feedbackResponses[index].data;
      });
    }

    tasks.value = initialTasks;

    if (project.value.id_evento && !eventoStore.getEventoById(project.value.id_evento)) {
      await eventoStore.fetchEventos();
    }

  } catch (err) {
    console.error("Erro ao buscar detalhes do projeto:", err);
    error.value = "Não foi possível carregar os detalhes do projeto.";
  } finally {
    loading.value = false;
  }
});

// --- FUNÇÃO PARA ABRIR O MODAL DE FEEDBACKS DA TAREFA ---
const openTaskFeedbackModal = async (task) => {
  selectedTaskForFeedback.value = { ...task, feedbacks: [] };
  isTaskFeedbackModalOpen.value = true;
  isFeedbackLoading.value = true;
  feedbackError.value = null;

  try {
    const url = `/tarefas/${task.id_tarefa}/feedbacks`;
    const { data } = await api.get(url);
    if (selectedTaskForFeedback.value) {
      selectedTaskForFeedback.value.feedbacks = data;
    }
  } catch (err) {
    console.error("Erro ao buscar feedbacks da tarefa:", err);
    feedbackError.value = "Não foi possível carregar os feedbacks. Tente novamente.";
  } finally {
    isFeedbackLoading.value = false;
  }
};


// --- Funções para o Modal de TAREFAS (Criar/Editar) ---
const isTaskModalOpen = ref(false);
const isTaskModalLoading = ref(false);
const currentTask = ref(null);
const taskModalConfig = {
  title: computed(() => (currentTask.value?.id_tarefa ? 'Editar Tarefa' : 'Nova Tarefa')),
  fields: [
    { key: 'descricao', label: 'Título da Tarefa', type: 'text', rules: [v => !!v || 'O título é obrigatório'] },
    { key: 'detalhe', label: 'Descrição (Opcional)', type: 'textarea' },
    { key: 'data_inicio_prevista', label: 'Início Previsto', type: 'date' },
    { key: 'data_fim_prevista', label: 'Fim Previsto', type: 'date' },
    { key: 'data_conclusao', label: 'Data de Conclusão', type: 'date' },
  ],
};
const openCreateTaskModal = () => {
  currentTask.value = { 
    descricao: '', 
    detalhe: '',
    data_inicio_prevista: null,
    data_fim_prevista: null,
    data_conclusao: null
  };
  isTaskModalOpen.value = true;
};
const openEditTaskModal = (task) => {
  currentTask.value = { ...task };
  isTaskModalOpen.value = true;
};
const handleSaveTask = async (formData) => {
  isTaskModalLoading.value = true;
  try {
    if (formData.id_tarefa) {
      const { data } = await api.put(`/tarefas/${formData.id_tarefa}`, formData);
      const index = tasks.value.findIndex(t => t.id_tarefa === data.id_tarefa);
      if (index !== -1) tasks.value[index] = data;
    } else {
      const payload = {
        id_projeto: project.value.id_projeto,
        id_situacao: 1, // 'A Fazer'
        ...formData,
      };
      const { data } = await api.post('/tarefas', payload);
      tasks.value.push(data);
    }
    isTaskModalOpen.value = false;
  } catch (err) {
    console.error("Erro ao salvar a tarefa:", err);
  } finally {
    isTaskModalLoading.value = false;
  }
};

// --- Funções do Kanban (Drag and Drop) ---
const handleDragStart = (event, task) => {
  event.dataTransfer.setData('text/plain', task.id_tarefa);
  event.dataTransfer.dropEffect = 'move';
};
const handleDrop = async (event, newStatus) => {
  const taskId = event.dataTransfer.getData('text/plain');
  const task = tasks.value.find(t => t.id_tarefa == taskId);
  if (task && task.id_situacao !== newStatus) {
    const originalStatus = task.id_situacao;
    task.id_situacao = newStatus; // Otimista
    try {
      await api.put(`/tarefas/${taskId}`, { id_situacao: newStatus });
    } catch (err) {
      task.id_situacao = originalStatus; // Reverte
    }
  }
};
const filterTasksByStatus = (status) => {
  return tasks.value.filter(task => task.id_situacao === status);
};

// --- Funções para formatação de data ---
const formatDate = (dateString) => {
  if (!dateString) return 'Data indefinida';
  return new Date(dateString).toLocaleDateString('pt-BR', {
    day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
  });
};

const formatDateSimple = (dateString) => {
  if (!dateString) return null;
  const date = new Date(dateString);
  const userTimezoneOffset = date.getTimezoneOffset() * 60000;
  return new Date(date.getTime() + userTimezoneOffset).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};
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
            <v-chip :color="projectStatus.color" :prepend-icon="projectStatus.icon" variant="tonal" label>
              {{ projectStatus.text }}
            </v-chip>
          </div>
        </v-card-item>
      </v-card>

      <v-card>
        <v-tabs v-model="activeTab" bg-color="green-darken-3" color="white" grow>
          <v-tab value="detalhes"><v-icon start>mdi-text-box-search-outline</v-icon> Detalhes</v-tab>
          <v-tab value="feedback"><v-icon start>mdi-comment-quote-outline</v-icon> Histórico de Feedbacks</v-tab>
          <v-tab value="equipe"><v-icon start>mdi-account-group-outline</v-icon> Equipe</v-tab>
          <v-tab value="tarefas" :disabled="project.id_situacao !== 2"><v-icon start>mdi-view-dashboard-outline</v-icon> Tarefas</v-tab>
        </v-tabs>

        <v-window v-model="activeTab">
          <v-window-item value="detalhes">
             <v-card-text class="pa-4 pa-md-6">
                <v-list lines="two" bg-color="transparent">
                    <v-list-item prepend-icon="mdi-lightbulb-on-outline" title="Problema a ser Resolvido" :subtitle="project.problema" class="mb-4"></v-list-item>
                    <v-list-item prepend-icon="mdi-bullseye-arrow" title="Relevância e Justificativa" :subtitle="project.relevancia" class="mb-4"></v-list-item>
                </v-list>
            </v-card-text>
          </v-window-item>

          <v-window-item value="feedback">
            <v-card-text class="pa-4 pa-md-6">
              <div v-if="combinedFeedbacks.length === 0" class="text-center pa-8 text-grey-darken-1">
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
                    <div class="text-caption opacity-75 mt-3">
                      Por: {{ fb.author }}
                    </div>
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
            <v-card-text v-else v-for="user in membros" class="text-center pa-8 text-grey-darken-1">
              <v-icon size="48" class="mb-4">mdi-account-group-outline</v-icon>
              <p>{{user.usuario.nome}}</p>
              <p>{{user.usuario.email}}</p>
            </v-card-text>
          </v-window-item>
          
          <v-window-item value="tarefas">
            <v-card-title class="d-flex justify-space-between align-center">
              <span>Quadro de Tarefas</span>
              <v-btn color="green" variant="flat" @click="openCreateTaskModal" prepend-icon="mdi-plus">
                Nova Tarefa
              </v-btn>
            </v-card-title>
            <v-card-text class="bg-grey-lighten-4">
              <v-row>
                <v-col v-for="column in kanbanColumns" :key="column.status" cols="12" md="4" @dragover.prevent @drop="handleDrop($event, column.status)">
                  <div class="pa-4 rounded-lg fill-height" :class="`bg-${column.color}-lighten-5`">
                    <div class="d-flex align-center mb-4">
                      <v-icon :color="column.color" class="mr-2">mdi-circle-medium</v-icon>
                      <span class="font-weight-bold text-grey-darken-3">{{ column.title }}</span>
                      <v-chip size="small" :color="column.color" class="ml-2">{{ filterTasksByStatus(column.status).length }}</v-chip>
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
                        <p v-if="task.detalhe" class="text-caption font-weight-regular text-grey-darken-1 mt-1">{{ task.detalhe }}</p>
                        
                        <div v-if="task.data_inicio_prevista || task.data_fim_prevista || task.data_conclusao" class="mt-3 d-flex flex-wrap ga-2">
                          <v-chip v-if="task.data_inicio_prevista" size="x-small" color="blue-grey" variant="tonal">
                            <v-icon start icon="mdi-calendar-arrow-right"></v-icon>
                            {{ formatDateSimple(task.data_inicio_prevista) }}
                            <v-tooltip activator="parent" location="top">Início Previsto</v-tooltip>
                          </v-chip>
                          <v-chip v-if="task.data_fim_prevista" size="x-small" color="blue-grey" variant="tonal">
                            <v-icon start icon="mdi-calendar-arrow-left"></v-icon>
                            {{ formatDateSimple(task.data_fim_prevista) }}
                            <v-tooltip activator="parent" location="top">Fim Previsto</v-tooltip>
                          </v-chip>
                           <v-chip v-if="task.data_conclusao" size="x-small" color="green" variant="tonal">
                            <v-icon start icon="mdi-calendar-check"></v-icon>
                            {{ formatDateSimple(task.data_conclusao) }}
                            <v-tooltip activator="parent" location="top">Data de Conclusão</v-tooltip>
                          </v-chip>
                        </div>

                      </v-card-text>
                      <v-card-actions class="pa-1">
                          <v-chip v-if="task.feedbacks && task.feedbacks.length > 0" size="x-small" prepend-icon="mdi-comment-text-outline" class="ml-2">
                            {{ task.feedbacks.length }}
                          </v-chip>
                          <v-spacer></v-spacer>
                          <v-btn color="primary" variant="text" size="small" @click="openTaskFeedbackModal(task)">Feedbacks</v-btn>
                          <v-btn icon="mdi-pencil" variant="text" size="x-small" @click="openEditTaskModal(task)"></v-btn>
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

    <CrudModal v-model="isTaskModalOpen" :title="taskModalConfig.title" :fields="taskModalConfig.fields" :item="currentTask" :loading="isTaskModalLoading" @save="handleSaveTask" />
    
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
            <div v-if="!selectedTaskForFeedback?.feedbacks || selectedTaskForFeedback.feedbacks.length === 0" class="text-center pa-8 text-grey-darken-1">
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
          <v-btn color="grey-darken-1" variant="text" @click="isTaskFeedbackModalOpen = false">Fechar</v-btn>
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
  border-left: 3px solid #E0E0E0;
  padding-left: 16px;
}
</style>
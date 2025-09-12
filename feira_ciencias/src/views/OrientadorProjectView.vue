<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../assets/plugins/axios.js';
import { useNotificationStore } from '@/stores/notification';

const router = useRouter();
const route = useRoute();
const notificationStore = useNotificationStore();

// --- ESTADOS DA PÁGINA ---
const carregando = ref(true);
const erro = ref(null);
const projeto = ref(null);
const tarefas = ref([]);

// --- ESTADOS DO COMPONENTE ---
const activeTab = ref('kanban');

// Estados do Modal de Feedback (Análise)
const isFeedbackModalOpen = ref(false);
const isFeedbackModalLoading = ref(false);
const tarefaSelecionada = ref(null);
const novoFeedback = ref('');

// --- ESTADOS PARA O MODAL DE CRIAR/EDITAR TAREFA ---
const isTaskModalOpen = ref(false);
const isTaskModalLoading = ref(false);
const currentTask = ref(null);
const taskFormData = ref({ descricao: '', detalhe: '' });

// --- LÓGICA DE BUSCA DE DADOS ---
onMounted(async () => {
  const projetoId = route.params.id;
  if (!projetoId) {
    erro.value = "ID do projeto não fornecido.";
    carregando.value = false;
    return;
  }
  try {
    const [projetoResponse, tarefasResponse] = await Promise.all([
      api.get(`/projetos/${projetoId}`),
      api.get(`/projetos/${projetoId}/tarefas`)
    ]);

    projeto.value = projetoResponse.data;
    const initialTasks = tarefasResponse.data;

    if (initialTasks && initialTasks.length > 0) {
      const feedbackPromises = initialTasks.map(tarefa =>
        api.get(`/tarefas/${tarefa.id_tarefa}/feedbacks`).catch(err => {
          console.warn(`Não foi possível buscar feedbacks para a tarefa ${tarefa.id_tarefa}:`, err);
          return { data: [] };
        })
      );
      const feedbackResponses = await Promise.all(feedbackPromises);
      initialTasks.forEach((tarefa, index) => {
        tarefa.feedbacks = feedbackResponses[index].data;
      });
    }
    tarefas.value = initialTasks;
  } catch (err) {
    console.error("Erro ao buscar dados do projeto:", err);
    erro.value = "Não foi possível carregar os dados do projeto. Tente novamente.";
  } finally {
    carregando.value = false;
  }
});

// --- CONFIGURAÇÕES E COMPUTEDS ---
const statusMap = {
  1: { text: 'Em Análise', color: 'orange-darken-2', icon: 'mdi-file-search-outline' },
  2: { text: 'Aprovado', color: 'green-darken-2', icon: 'mdi-check-decagram-outline' },
};

const kanbanColumns = [
  { title: 'A Fazer', status: 1, color: 'grey' },
  { title: 'Em Andamento', status: 2, color: 'blue' },
  { title: 'Concluído', status: 3, color: 'green' },
];

const projetoStatus = computed(() => {
    if (!projeto.value) return {};
    return statusMap[projeto.value.id_situacao] || { text: 'Desconhecido', color: 'grey' };
});

const taskModalTitle = computed(() => {
  return currentTask.value ? 'Editar Tarefa' : 'Nova Tarefa';
});

const filterTasksByStatus = (status) => tarefas.value.filter(task => task.id_situacao === status);

// --- FUNÇÕES DE DRAG AND DROP ---
const handleDragStart = (event, tarefa) => {
  event.dataTransfer.clearData();
  event.dataTransfer.setData('text/plain', tarefa.id_tarefa);
  event.dataTransfer.dropEffect = 'move';
};

const handleDrop = async (event, newStatus) => {
  const tarefaId = event.dataTransfer.getData('text/plain');
  const tarefa = tarefas.value.find(t => t.id_tarefa == tarefaId);

  if (tarefa && tarefa.id_situacao !== newStatus) {
    const originalStatus = tarefa.id_situacao;
    tarefa.id_situacao = newStatus;
    try {
      await api.put(`/tarefas/${tarefaId}`, { id_situacao: newStatus });
      notificationStore.showSuccess('Status da tarefa atualizado!');
    } catch (err) {
      tarefa.id_situacao = originalStatus;
      notificationStore.showError('Não foi possível atualizar o status da tarefa.');
      console.error("Erro ao mover tarefa:", err);
    }
  }
};

// --- FUNÇÕES PARA MODAIS ---
const openFeedbackModal = (tarefa) => {
  tarefaSelecionada.value = tarefa;
  novoFeedback.value = '';
  isFeedbackModalOpen.value = true;
};

const closeFeedbackModal = () => {
  isFeedbackModalOpen.value = false;
  setTimeout(() => { tarefaSelecionada.value = null; }, 300);
};

const submeterFeedback = async () => {
  if (!novoFeedback.value.trim()) {
    notificationStore.showWarning('O feedback não pode estar vazio.');
    return;
  }
  isFeedbackModalLoading.value = true;
  try {
    const response = await api.post(`/tarefas/${tarefaSelecionada.value.id_tarefa}/feedbacks`, {
      feedback: novoFeedback.value,
    });
    if (!tarefaSelecionada.value.feedbacks) {
        tarefaSelecionada.value.feedbacks = [];
    }
    tarefaSelecionada.value.feedbacks.push(response.data);
    notificationStore.showSuccess('Feedback enviado com sucesso!');
    closeFeedbackModal();
  } catch (err) {
    console.error("Erro ao submeter feedback:", err);
    notificationStore.showError('Não foi possível enviar o feedback.');
  } finally {
    isFeedbackModalLoading.value = false;
  }
};

const openCreateTaskModal = () => {
  currentTask.value = null;
  taskFormData.value = { descricao: '', detalhe: '' };
  isTaskModalOpen.value = true;
};

const openEditTaskModal = (tarefa) => {
  currentTask.value = tarefa;
  taskFormData.value = { ...tarefa };
  isTaskModalOpen.value = true;
};

const handleSaveTask = async () => {
  isTaskModalLoading.value = true;
  try {
    if (currentTask.value) {
      const { data } = await api.put(`/tarefas/${currentTask.value.id_tarefa}`, taskFormData.value);
      const index = tarefas.value.findIndex(t => t.id_tarefa === data.id_tarefa);
      if (index !== -1) tarefas.value[index] = data;
      notificationStore.showSuccess('Tarefa atualizada com sucesso!');
    } else {
      const payload = {
        id_projeto: projeto.value.id_projeto,
        id_situacao: 1,
        ...taskFormData.value,
      };
      const { data } = await api.post('/tarefas', payload);
      tarefas.value.push(data);
      notificationStore.showSuccess('Tarefa criada com sucesso!');
    }
    isTaskModalOpen.value = false;
  } catch (err) {
    console.error("Erro ao salvar a tarefa:", err);
    notificationStore.showError('Não foi possível salvar a tarefa.');
  } finally {
    isTaskModalLoading.value = false;
  }
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
  <v-container fluid>
    <v-btn variant="text" prepend-icon="mdi-arrow-left" @click="router.go(-1)" class="mb-6">
      Voltar
    </v-btn>

    <div v-if="carregando" class="text-center py-16">
      <v-progress-circular indeterminate color="green-darken-3" size="64"></v-progress-circular>
    </div>
    <v-alert v-else-if="erro" type="error" variant="tonal" prominent>{{ erro }}</v-alert>

    <div v-else-if="projeto">
      <v-card theme="dark" class="mb-8 bg-green-darken-4">
        <v-card-item class="pa-4 pa-sm-6">
          <div class="d-flex flex-wrap justify-space-between align-center">
            <div>
              <p class="text-overline">Gerenciamento do Projeto</p>
              <h1 class="text-h4 font-weight-bold">{{ projeto.titulo }}</h1>
            </div>
            <v-chip :color="projetoStatus.color" :prepend-icon="projetoStatus.icon" variant="tonal" label>
              {{ projetoStatus.text }}
            </v-chip>
          </div>
        </v-card-item>
      </v-card>

      <v-card>
        <v-tabs v-model="activeTab" bg-color="green-darken-3" color="white" grow>
          <v-tab value="kanban"><v-icon start>mdi-view-dashboard-outline</v-icon>Quadro de Tarefas</v-tab>
          <v-tab value="detalhes"><v-icon start>mdi-text-box-search-outline</v-icon>Detalhes do projeto</v-tab>
        </v-tabs>

        <v-window v-model="activeTab">
          <v-window-item value="kanban">
            <v-card-title class="d-flex justify-space-between align-center">
              <span>Andamento</span>
              <v-btn color="green" variant="flat" @click="openCreateTaskModal" prepend-icon="mdi-plus">
                Nova Tarefa
              </v-btn>
            </v-card-title>
            <v-card-text class="bg-grey-lighten-4 pa-4 pa-md-6">
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
                      <v-chip size="small" :color="column.color" class="ml-2">{{ filterTasksByStatus(column.status).length }}</v-chip>
                    </div>
                    
                    <v-card
                      v-for="tarefa in filterTasksByStatus(column.status)"
                      :key="tarefa.id_tarefa"
                      class="mb-3 task-card"
                      theme="light"
                      variant="flat"
                      draggable="true"
                      @dragstart="handleDragStart($event, tarefa)"
                      >
                      <v-card-text class="pb-0">
                        <p class="font-weight-medium text-grey-darken-4">{{ tarefa.descricao }}</p>
                        <p v-if="tarefa.detalhe" class="text-caption font-weight-regular text-grey-darken-1 mt-1">{{ tarefa.detalhe }}</p>
                      </v-card-text>
                      <v-card-actions>
                        <v-chip v-if="tarefa.feedbacks && tarefa.feedbacks.length > 0" size="x-small" prepend-icon="mdi-comment-text-multiple-outline" color="blue-grey" variant="tonal">
                          {{ tarefa.feedbacks.length }}
                        </v-chip>
                        <v-spacer></v-spacer>
                        <v-btn color="grey-darken-3" variant="text" size="small" @click.stop="openFeedbackModal(tarefa)">
                          Analisar
                        </v-btn>
                        <v-btn icon="mdi-pencil" variant="text" size="x-small" @click.stop="openEditTaskModal(tarefa)"></v-btn>
                      </v-card-actions>
                    </v-card>
                  </div>
                </v-col>
              </v-row>
            </v-card-text>
          </v-window-item>

          <v-window-item value="detalhes">
            <v-card-text class="pa-4 pa-md-6">
                <v-list lines="two" bg-color="transparent">
                  <v-list-item
                    prepend-icon="mdi-lightbulb-on-outline"
                    title="Problema a ser Resolvido"
                    :subtitle="projeto.problema || 'Não informado'"
                  ></v-list-item>
                  <v-list-item
                    class="mt-4"
                    prepend-icon="mdi-bullseye-arrow"
                    title="Relevância e Justificativa"
                    :subtitle="projeto.relevancia || 'Não informado'"
                  ></v-list-item>
                </v-list>
            </v-card-text>
          </v-window-item>
        </v-window>
      </v-card>
    </div>

    <v-dialog v-model="isTaskModalOpen" persistent max-width="600px">
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
              :rules="[v => !!v || 'O título é obrigatório']"
              autofocus
            ></v-text-field>
            <v-textarea
              v-model="taskFormData.detalhe"
              label="Descrição Detalhada (Opcional)"
              variant="outlined"
              rows="3"
              class="mt-4"
            ></v-textarea>
          </v-form>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn color="grey-darken-1" variant="text" @click="isTaskModalOpen = false" :disabled="isTaskModalLoading">Cancelar</v-btn>
          <v-btn color="green-darken-2" variant="flat" @click="handleSaveTask" :loading="isTaskModalLoading">Salvar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="isFeedbackModalOpen" persistent max-width="700px">
      <v-card>
        <v-card-title class="d-flex align-center text-h5 bg-green-darken-3 text-white">
          <v-icon start>mdi-comment-edit-outline</v-icon>
          Analisar Tarefa
          <v-spacer></v-spacer>
          <v-btn icon="mdi-close" variant="text" @click="closeFeedbackModal"></v-btn>
        </v-card-title>
        <v-card-text class="pt-6">
          <p class="font-weight-bold text-h6 text-grey-darken-4">{{ tarefaSelecionada?.descricao }}</p>
          <p class="text-body-2 text-grey-darken-2 mt-1">{{ tarefaSelecionada?.detalhe }}</p>
          <v-divider class="my-6"></v-divider>
          <h3 class="text-h6 font-weight-medium mb-4 text-grey-darken-3">Histórico de Feedbacks</h3>
          <div v-if="!tarefaSelecionada?.feedbacks || tarefaSelecionada?.feedbacks.length === 0" class="text-center text-grey-darken-1 pa-4">
            <p>Nenhum feedback registrado para esta tarefa ainda.</p>
          </div>
          <v-timeline v-else side="end" align="start" density="compact" truncate-line="both">
            <v-timeline-item v-for="feedback in tarefaSelecionada?.feedbacks" :key="feedback.id_feedback" dot-color="green-darken-1" size="small">
              <div class="feedback-item">
                <p class="font-italic">"{{ feedback.feedback }}"</p>
                <div class="text-caption text-grey-darken-1 mt-1">
                  - {{ feedback.usuario?.nome || 'Orientador' }} em {{ formatDate(feedback.created_at) }}
                </div>
              </div>
            </v-timeline-item>
          </v-timeline>
          <v-divider class="my-6"></v-divider>
          <h3 class="text-h6 font-weight-medium mb-4 text-grey-darken-3">Adicionar Novo Feedback</h3>
          <v-textarea
            v-model="novoFeedback"
            label="Seu parecer sobre a tarefa"
            placeholder="Descreva os pontos positivos, áreas para melhoria, ou próximos passos..."
            rows="4"
            variant="outlined"
          ></v-textarea>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn color="grey-darken-1" variant="text" @click="closeFeedbackModal" :disabled="isFeedbackModalLoading">Cancelar</v-btn>
          <v-btn :color="`green-darken-2`" variant="flat" @click="submeterFeedback" :loading="isFeedbackModalLoading">
            Enviar Feedback
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
  transition: box-shadow 0.2s ease-in-out, transform 0.2s ease-in-out;
}
.task-card:hover {
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}
.feedback-item {
  border-left: 3px solid #E0E0E0;
  padding-left: 16px;
}
</style>
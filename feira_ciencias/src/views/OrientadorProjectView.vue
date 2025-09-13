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
const membros = ref([]); // Estado para armazenar os membros da equipe

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
const taskFormData = ref({
  descricao: '',
  detalhe: '',
  data_inicio_prevista: null,
  data_fim_prevista: null,
  data_conclusao: null,
  id_usuario_atribuido: null, // Campo para associar a tarefa a um usuário
});

// --- ESTADOS PARA O MODAL DE APAGAR TAREFA ---
const isDeleteTaskDialogOpen = ref(false);
const taskToDelete = ref(null);


// --- LÓGICA DE BUSCA DE DADOS ---
onMounted(async () => {
  const projetoId = route.params.id;
  if (!projetoId) {
    erro.value = "ID do projeto não fornecido.";
    carregando.value = false;
    return;
  }
  try {
    // Busca todos os dados necessários em paralelo para otimizar o carregamento
    const [projetoResponse, tarefasResponse, membrosResponse] = await Promise.all([
      api.get(`/projetos/${projetoId}`),
      api.get(`/projetos/${projetoId}/tarefas`),
      api.get(`/membros_projeto/${projetoId}`) // Busca os membros da equipe
    ]);

    projeto.value = projetoResponse.data.data || projetoResponse.data;
    membros.value = membrosResponse.data.data || membrosResponse.data || [];
    const initialTasks = tarefasResponse.data.data || tarefasResponse.data || [];

    // Processa as tarefas para encontrar o responsável atual a partir do último registro
    if (Array.isArray(initialTasks) && initialTasks.length > 0) {
      const taskDetailPromises = initialTasks.map(task => {
        const registrationsPromise = api.get(`/registros_tarefas?id_tarefa=${task.id_tarefa}`).catch(() => ({ data: [] }));
        const feedbacksPromise = api.get(`/tarefas/${task.id_tarefa}/feedbacks`).catch(() => ({ data: [] }));
        return Promise.all([registrationsPromise, feedbacksPromise]);
      });

      const allTaskDetails = await Promise.all(taskDetailPromises);

      initialTasks.forEach((task, index) => {
        const [registrationResponse, feedbackResponse] = allTaskDetails[index];
        
        const registrations = registrationResponse.data.data || registrationResponse.data || [];
        if (registrations.length > 0) {
          task.id_usuario_atribuido = registrations[registrations.length - 1].id_responsavel;
        }

        task.feedbacks = feedbackResponse.data.data || feedbackResponse.data || [];
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

// --- FUNÇÕES DE NAVEGAÇÃO E AUXILIARES ---
const goToProjectSettings = () => {
    if (projeto.value) {
        router.push(`/gerenciar-projeto/${projeto.value.id_projeto}`);
    }
};

const getMemberName = (userId) => {
    if (!userId || !membros.value || membros.value.length === 0) return 'Não atribuído';
    const membro = membros.value.find(m => m.id_usuario === userId);
    return membro ? membro.usuario.nome : 'Não atribuído';
};


// --- FUNÇÕES DE DRAG AND DROP ---
const handleDragStart = (event, tarefa) => {
  event.dataTransfer.clearData();
  event.dataTransfer.setData('text/plain', String(tarefa.id_tarefa));
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
  taskFormData.value = {
    descricao: '',
    detalhe: '',
    data_inicio_prevista: null,
    data_fim_prevista: null,
    data_conclusao: null,
    id_usuario_atribuido: null,
  };
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
    let savedTaskData;
    const { id_usuario_atribuido, ...coreTaskData } = taskFormData.value;

    if (currentTask.value) {
      const { data } = await api.put(`/tarefas/${currentTask.value.id_tarefa}`, coreTaskData);
      savedTaskData = data.data || data;
    } else {
      const payload = {
        id_projeto: projeto.value.id_projeto,
        id_situacao: 1, // 'A Fazer' por padrão
        ...coreTaskData,
      };
      const { data } = await api.post('/tarefas', payload);
      savedTaskData = data.data || data;
    }

    if (id_usuario_atribuido) {
        await api.post('/registros_tarefas', {
            id_tarefa: savedTaskData.id_tarefa,
            id_responsavel: id_usuario_atribuido,
            descricao_atividade: `Tarefa atribuída ao responsável.`,
            resultado: null,
            data_execucao: new Date().toISOString().split('T')[0],
            arquivo: 'null',
        });
    }

    savedTaskData.id_usuario_atribuido = id_usuario_atribuido;
    
    if (currentTask.value) {
      const index = tarefas.value.findIndex(t => t.id_tarefa === savedTaskData.id_tarefa);
      if (index !== -1) {
        savedTaskData.feedbacks = tarefas.value[index].feedbacks;
        tarefas.value[index] = savedTaskData;
      }
    } else {
      tarefas.value.push(savedTaskData);
    }
    
    notificationStore.showSuccess('Tarefa salva com sucesso!');
    isTaskModalOpen.value = false;

  } catch (err) {
    console.error("Erro ao salvar a tarefa:", err);
    notificationStore.showError('Não foi possível salvar a tarefa.');
  } finally {
    isTaskModalLoading.value = false;
  }
};

const openDeleteTaskModal = (tarefa) => {
  taskToDelete.value = tarefa;
  isDeleteTaskDialogOpen.value = true;
};

const confirmDeleteTask = async () => {
  if (!taskToDelete.value) return;
  notificationStore.showInfo('Apagando tarefa...');
  try {
    await api.delete(`/tarefas/${taskToDelete.value.id_tarefa}`);
    tarefas.value = tarefas.value.filter(t => t.id_tarefa !== taskToDelete.value.id_tarefa);
    notificationStore.showSuccess('Tarefa apagada com sucesso!');
  } catch (err) {
    console.error("Erro ao apagar tarefa:", err);
    notificationStore.showError('Não foi possível apagar a tarefa.');
  } finally {
    isDeleteTaskDialogOpen.value = false;
    taskToDelete.value = null;
  }
};

// --- FUNÇÕES DE FORMATAÇÃO DE DATA ---
const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const formatDateSimple = (dateString) => {
  if (!dateString) return null;
  const date = new Date(dateString);
  const userTimezoneOffset = date.getTimezoneOffset() * 60000;
  return new Date(date.getTime() - userTimezoneOffset).toISOString().split('T')[0];
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
            <v-row align="center" justify="space-between">
                <v-col cols="12" md="auto">
                    <p class="text-overline">Acompanhamento de Tarefas</p>
                    <h1 class="text-h4 font-weight-bold">{{ projeto.titulo }}</h1>
                </v-col>
                <v-col cols="12" md="4" class="mt-4 mt-md-0 text-md-right">
                    <v-btn
                        size="large"
                        color="white"
                        variant="outlined"
                        prepend-icon="mdi-cog-outline"
                        @click="goToProjectSettings"
                        block
                    >
                        Configurar Projeto
                    </v-btn>
                </v-col>
            </v-row>
        </v-card-item>
      </v-card>

      <v-card>
        <v-tabs v-model="activeTab" bg-color="green-darken-3" color="white" grow>
          <v-tab value="kanban"><v-icon start>mdi-view-dashboard-outline</v-icon>Quadro de Tarefas</v-tab>
          <v-tab value="membros"><v-icon start>mdi-account-group-outline</v-icon>Equipe</v-tab>
          <v-tab value="detalhes"><v-icon start>mdi-text-box-search-outline</v-icon>Detalhes da Proposta</v-tab>
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
                       <v-card-text class="font-weight-medium text-grey-darken-4 pb-1">
                        {{ tarefa.descricao }}
                        <p v-if="tarefa.detalhe" class="text-caption font-weight-regular text-grey-darken-1 mt-1">{{ tarefa.detalhe }}</p>
                         <div v-if="tarefa.data_inicio_prevista || tarefa.data_fim_prevista" class="mt-3 d-flex flex-wrap ga-2">
                            <v-chip v-if="tarefa.data_inicio_prevista" size="x-small" color="blue-grey" variant="tonal">
                                <v-icon start icon="mdi-calendar-arrow-right"></v-icon>
                                {{ formatDateSimple(tarefa.data_inicio_prevista) }}
                                <v-tooltip activator="parent" location="top">Início Previsto</v-tooltip>
                            </v-chip>
                            <v-chip v-if="tarefa.data_fim_prevista" size="x-small" color="blue-grey" variant="tonal">
                                <v-icon start icon="mdi-calendar-arrow-left"></v-icon>
                                {{ formatDateSimple(tarefa.data_fim_prevista) }}
                                <v-tooltip activator="parent" location="top">Fim Previsto</v-tooltip>
                            </v-chip>
                        </div>
                      </v-card-text>
                      <v-card-actions class="pt-0 px-4 pb-2">
                        <v-chip v-if="tarefa.feedbacks && tarefa.feedbacks.length > 0" size="x-small" prepend-icon="mdi-comment-text-multiple-outline" color="blue-grey" variant="tonal" label>
                          {{ tarefa.feedbacks.length }}
                        </v-chip>
                        <v-chip v-if="tarefa.id_usuario_atribuido" size="small" class="ml-2" color="green" variant="tonal" label>
                            <v-icon start icon="mdi-account-outline"></v-icon>
                            {{ getMemberName(tarefa.id_usuario_atribuido) }}
                        </v-chip>
                        <v-spacer></v-spacer>
                        <v-btn icon="mdi-pencil" variant="text" size="x-small" @click.stop="openEditTaskModal(tarefa)"></v-btn>
                        <v-btn icon="mdi-delete-outline" color="red" variant="text" size="x-small" @click.stop="openDeleteTaskModal(tarefa)"></v-btn>
                         <v-btn color="grey-darken-3" variant="text" size="small" @click.stop="openFeedbackModal(tarefa)">
                           Analisar
                        </v-btn>
                      </v-card-actions>
                    </v-card>
                  </div>
                </v-col>
              </v-row>
            </v-card-text>
          </v-window-item>

          <v-window-item value="membros">
              <v-card-text class="pa-0">
                  <v-list lines="two" v-if="membros.length > 0">
                      <v-list-subheader>Membros da Equipe</v-list-subheader>
                      <v-list-item v-for="membro in membros" :key="membro.id_usuario" :title="membro.usuario.nome" :subtitle="membro.usuario.email">
                          <template v-slot:prepend>
                              <v-avatar color="green-darken-4">
                                  <span class="text-h6">{{ membro.usuario.nome.charAt(0) }}</span>
                              </v-avatar>
                          </template>
                      </v-list-item>
                  </v-list>
                  <v-alert v-else type="info" variant="tonal" class="ma-4">Nenhum membro na equipe deste projeto.</v-alert>
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
          <v-btn color="grey-darken-1" variant="text" @click="isTaskModalOpen = false" :disabled="isTaskModalLoading">Cancelar</v-btn>
          <v-btn color="green-darken-2" variant="flat" @click="handleSaveTask" :loading="isTaskModalLoading">Salvar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- MODAL DE FEEDBACK -->
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
    
    <!-- DIÁLOGO DE CONFIRMAÇÃO PARA APAGAR TAREFA -->
    <v-dialog v-model="isDeleteTaskDialogOpen" persistent max-width="500px">
      <v-card>
        <v-card-title class="text-h5">
          <v-icon color="red" start>mdi-alert-circle-outline</v-icon>
          Confirmar Exclusão
        </v-card-title>
        <v-card-text>
          Você tem certeza que deseja apagar a tarefa <strong>"{{ taskToDelete?.descricao }}"</strong>?
          <br><br>
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
  transform: translateY(-px);
}
.feedback-item {
  border-left: 3px solid #E0E0E0;
  padding-left: 16px;
}
</style>


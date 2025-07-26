<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../assets/plugins/axios.js'; // Ajuste o caminho para o seu arquivo de config do Axios
import CrudModal from '@/components/CrudModal.vue'; // 1. Importe o seu modal de CRUD
import { useEventoStore } from '@/stores/eventoStore';

const route = useRoute();
const router = useRouter();

const eventoStore = useEventoStore();

// --- Estado do Componente ---
const project = ref(null);
const tasks = ref([]);
const loading = ref(true);
const error = ref(null);

//mapa dos estatus dos projetos

const statusMap = {
  1: { text: 'Em Elaboração', color: 'white-darken-2', icon: 'mdi-pencil-ruler' },
  2: { text: 'Aprovado', color: 'green-darken-2', icon: 'mdi-check-decagram' },
  3: { text: 'Reprovado', color: 'red-darken-2', icon: 'mdi-close-octagon' },

};


// --- Config kanban
const kanbanColumns = [
  { title: 'A Fazer', status: 1, color: 'grey' },
  { title: 'Em Andamento', status: 2, color: 'blue' },
  { title: 'Concluído', status: 3, color: 'green' },
];

// --- Estado para o Modal de Tarefas ---

const isTaskModalOpen = ref(false);
const isTaskModalLoading = ref(false);
const currentTask = ref(null);

const taskModalConfig = {
  title: computed(() => (currentTask.value ? 'Editar Tarefa' : 'Nova Tarefa')),
  fields: [
    { key: 'descricao', label: 'Título da Tarefa', type: 'text', rules: [v => !!v || 'O título é obrigatório'] },
    { key: 'detalhe', label: 'Descrição (Opcional)', type: 'textarea' },
  ],
};

const event = computed(() => {
  if (project.value && project.value.id_evento) {
    return eventoStore.getEventoById(project.value.id_evento);
  }
  return null;
});

// A computed para o status do projeto também continua igual.
const projectStatus = computed(() => statusMap[project.value?.id_situacao] || {});


// --- Configuração para o Modal de Tarefas ---

onMounted(async () => {
  const projectId = route.params.id;
  try {
    const promises = [
      api.get(`/projetos/${projectId}`),
      api.get(`/projetos/${projectId}/tarefas`)
    ];

    if (eventoStore.eventos.length === 0) {
      console.log("Store de eventos vazia. Buscando eventos...");
      promises.push(eventoStore.fetchEventos());
    }

    const [projectResponse, tasksResponse] = await Promise.all(promises);

    project.value = projectResponse.data;
    tasks.value = tasksResponse.data;

  } catch (err) {
    console.error("Erro ao buscar detalhes do projeto:", err);
    error.value = "Não foi possível carregar os detalhes do projeto.";
  } finally {
    loading.value = false;
  }
});

// --- Funções para o Modal de Tarefas ---
const openCreateTaskModal = () => {
  currentTask.value = null;
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
        id_situacao: 1,
        detalhe: formData.detalhe,
        descricao: formData.descricao,
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
  const taskIndex = tasks.value.findIndex(t => t.id_tarefa == taskId);

  if (taskIndex === -1) return;

  const task = tasks.value[taskIndex];

  if (task && task.id_situacao !== newStatus) {

    const originalTask = { ...task };

    const updatedTask = { ...task, id_situacao: newStatus };

    tasks.value.splice(taskIndex, 1, updatedTask);

    try {
      await api.put(`/tarefas/${taskId}`, { id_situacao: newStatus });
    } catch (err) {
      console.error("Falha ao atualizar o status da tarefa:", err);
      tasks.value.splice(taskIndex, 1, originalTask);
    }
  }
};

const filterTasksByStatus = (status) => {
  return tasks.value.filter(task => (task.status === status || task.id_situacao === status));
};

// --- Funções para o modal generico ---
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const isModalLoading = ref(false);

const modalConfig = {
  title: 'Editar Projeto',
  fields: [
    { key: 'titulo', label: 'Título do Projeto', type: 'text', rules: [v => !!v || 'O título é obrigatório'] },
    { key: 'problema', label: 'Problema a ser Resolvido', type: 'textarea', rules: [v => !!v || 'A descrição do problema é obrigatória'] },
    { key: 'relevancia', label: 'Relevância e Justificativa', type: 'textarea', rules: [v => !!v || 'A relevância é obrigatória'] },
  ],
};

const openEditModal = () => {
  isEditModalOpen.value = true;
};

const openDeleteModal = () => {
  isDeleteModalOpen.value = true;
};

const handleUpdate = async (formData) => {
  isModalLoading.value = true;
  try {
    const { data } = await api.put(`/projetos/${project.value.id_projeto}`, formData);
    project.value = data;
    isEditModalOpen.value = false;
  } catch (err) {
    console.error("Erro ao atualizar o projeto:", err);
  } finally {
    isModalLoading.value = false;
  }
};

const handleDelete = async () => {
  isModalLoading.value = true;
  try {
    await api.delete(`/projetos/${project.value.id_projeto}`);
    isDeleteModalOpen.value = false; // Fecha o modal
    router.push({ name: 'home' }); // Redireciona para a home, pois o projeto não existe mais
  } catch (err) {
    console.error("Erro ao excluir o projeto:", err);
  } finally {
    isModalLoading.value = false;
  }
};
const formatDate = (dateString) => {
  if (!dateString) return 'Não definida';
  return new Date(dateString).toLocaleDateString('pt-BR');
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
              <div v-if="event" class="d-flex align-center mt-2 opacity-75">
                <v-icon size="small" start icon="mdi-calendar-star"></v-icon>
                <span class="text-subtitle-1">{{ event.nome }}</span>
              </div>
            </div>

            <div class="d-flex align-center mt-4 mt-sm-0">
              <v-chip :color="projectStatus.color" :prepend-icon="projectStatus.icon" variant="tonal" class="mr-4"
                label>
                {{ projectStatus.text }}
              </v-chip>

              <v-btn v-if="project.id_situacao === 2" color="white" variant="outlined" @click="openCreateTaskModal"
                prepend-icon="mdi-plus">
                Adicionar Tarefa
              </v-btn>
            </div>
          </div>
        </v-card-item>
      </v-card>

      <v-row v-if="project.id_situacao === 2">
        <v-col v-for="column in kanbanColumns" :key="column.status" cols="12" md="4" @dragover.prevent
          @drop="handleDrop($event, column.status)">
          <div class="pa-4 rounded-lg fill-height" :class="`bg-${column.color}-lighten-5`">
            <div class="d-flex align-center mb-4">
              <v-icon :color="column.color" class="mr-2">mdi-circle-medium</v-icon>
              <span class="font-weight-bold text-grey-darken-3">{{ column.title }}</span>
              <v-chip size="small" :color="column.color" class="ml-2">{{ filterTasksByStatus(column.status).length
                }}</v-chip>
            </div>

            <div v-if="filterTasksByStatus(column.status).length === 0" class="text-center text-grey-darken-1 pa-4">
              Nenhuma tarefa aqui.
            </div>

            <v-card v-for="task in filterTasksByStatus(column.status)" :key="task.id_tarefa" class="mb-3 task-card"
              variant="flat" draggable="true" @dragstart="handleDragStart($event, task)">
              <v-card-text class="font-weight-medium text-grey-darken-4">
                {{ task.descricao }}
                <p v-if="task.detalhe" class="text-caption font-weight-regular text-grey-darken-1 mt-1">{{ task.detalhe
                  }}</p>
              </v-card-text>
              <v-card-actions class="pa-1">
                <v-spacer></v-spacer>
                <v-btn icon="mdi-pencil" variant="text" size="x-small" @click="openEditTaskModal(task)"></v-btn>
              </v-card-actions>
            </v-card>
          </div>
        </v-col>
      </v-row>

      <v-card v-else class="mb-8 bg-green-darken-4">
        <v-card-item class="pb-0">
          <v-card-title class="d-flex align-center">
            <v-icon start icon="mdi-text-box-search-outline"></v-icon>
            Detalhes do Projeto
          </v-card-title>
          <v-card-subtitle>Informações sobre a proposta</v-card-subtitle>
        </v-card-item>

        <v-list bg-color="transparent" class="mt-2">
          <v-list-item prepend-icon="mdi-lightbulb-on-outline">
            <v-list-item-title class="text-overline">Problema a ser Resolvido</v-list-item-title>
            <v-list-item-subtitle class="text-body-1 text-wrap">{{ project.problema }}</v-list-item-subtitle>
          </v-list-item>

          <v-list-item prepend-icon="mdi-lightbulb-on-outline">
            <v-list-item-title class="text-overline">Relevância e Justificativa</v-list-item-title>
            <v-list-item-subtitle class="text-body-1 text-wrap">{{ project.relevancia }}</v-list-item-subtitle>
          </v-list-item>

          <v-list-item prepend-icon="mdi-calendar-plus">
            <v-list-item-title class="text-overline">Data de Criação</v-list-item-title>
            <v-list-item-subtitle class="text-body-1">{{ formatDate(project.data_criacao) }}</v-list-item-subtitle>
          </v-list-item>
          <v-list-item prepend-icon="mdi-account-group-outline">
            <v-list-item-title class="text-overline">Orientador(a)</v-list-item-title>
            <v-list-item-subtitle class="text-body-1">{{ project?.orientador?.nome }}</v-list-item-subtitle>
          </v-list-item>
          <v-list-item prepend-icon="mdi-account-group-outline">
            <v-list-item-title class="text-overline">Coorientador(a)</v-list-item-title>
            <v-list-item-subtitle class="text-body-1">{{ project?.coorientador?.nome }}</v-list-item-subtitle>
          </v-list-item>
        </v-list>

        <v-divider></v-divider>

        <v-card-actions class="pa-3">
          <v-spacer></v-spacer>
          <v-btn variant="tonal" @click="openEditModal">Editar Proposta</v-btn>
          <v-btn color="red-lighten-2" variant="text" @click="openDeleteModal">Excluir</v-btn>
        </v-card-actions>
      </v-card>

    </div>

    <CrudModal v-model="isTaskModalOpen" :title="taskModalConfig.title" :fields="taskModalConfig.fields"
      :item="currentTask" :loading="isTaskModalLoading" @save="handleSaveTask" />

    <CrudModal v-model="isEditModalOpen" :title="modalConfig.title" :fields="modalConfig.fields" :item="project"
      :loading="isModalLoading" @save="handleUpdate" />

    <v-dialog v-model="isDeleteModalOpen" max-width="450">
      <v-card prepend-icon="mdi-alert-circle-outline" title="Confirmar Exclusão">
        <v-card-text>
          Você tem certeza que deseja excluir o projeto **{{ project?.titulo }}**? Esta ação não pode ser desfeita.
        </v-card-text>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="isDeleteModalOpen = false" :disabled="isModalLoading">Cancelar</v-btn>
          <v-btn color="red-darken-2" variant="flat" @click="handleDelete" :loading="isModalLoading">
            Excluir
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

  </v-container>
</template>

<style scoped>
.fill-height {
  height: 100%;
}

.task-card {
  cursor: move;
  transition: box-shadow 0.2s ease-in-out;
}

.task-card:hover {
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
</style>
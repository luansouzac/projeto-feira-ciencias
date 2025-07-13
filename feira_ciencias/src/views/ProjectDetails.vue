<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../assets/plugins/axios.js'; // Ajuste o caminho para o seu arquivo de config do Axios
import CrudModal from '@/components/CrudModal.vue'; // 1. Importe o seu modal de CRUD

const route = useRoute();
const router = useRouter();

// --- Estado do Componente ---
const project = ref(null);
const tasks = ref([]); 
const loading = ref(true);
const error = ref(null);

// --- Estado para o Modal de Tarefas ---

const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const isModalLoading = ref(false);

const isTaskModalOpen = ref(false);
const isTaskModalLoading = ref(false);
const currentTask = ref(null);

const kanbanColumns = [
  { title: 'A Fazer', status: 1, color: 'grey' },
  { title: 'Em Andamento', status: 2, color: 'blue' },
  { title: 'Concluído', status: 3, color: 'green' },
];

const modalConfig = {
  title: 'Editar Projeto',
  fields: [
    { key: 'titulo', label: 'Título do Projeto', type: 'text', rules: [v => !!v || 'O título é obrigatório'] },
    { key: 'problema', label: 'Problema a ser Resolvido', type: 'textarea', rules: [v => !!v || 'A descrição do problema é obrigatória'] },
    { key: 'relevancia', label: 'Relevância e Justificativa', type: 'textarea', rules: [v => !!v || 'A relevância é obrigatória'] },
  ],
};

// --- Configuração para o Modal de Tarefas ---
const taskModalConfig = {
  title: computed(() => (currentTask.value ? 'Editar Tarefa' : 'Nova Tarefa')),
  fields: [
    { key: 'descricao', label: 'Título da Tarefa', type: 'text', rules: [v => !!v || 'O título é obrigatório'] },
    { key: 'detalhe', label: 'Descrição (Opcional)', type: 'textarea' },
  ],
};

const statusMap = {
  1: { text: 'Em Elaboração', color: 'orange-darken-2', icon: 'mdi-pencil-ruler' },
  2: { text: 'Aprovado', color: 'green-darken-2', icon: 'mdi-check-decagram' },
  3: { text: 'Reprovado', color: 'red-darken-2', icon: 'mdi-close-octagon' },
};

onMounted(async () => {
  const projectId = route.params.id;
  try {
    const [projectResponse, tasksResponse] = await Promise.all([
      api.get(`/projetos/${projectId}`),
      api.get(`/projetos/${projectId}/tarefas`) // Assumindo que esta rota existe
    ]);
    
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
    // Guarda uma cópia do objeto original para reverter em caso de falha
    const originalTask = { ...task };

    // Cria um novo objeto de tarefa com o status atualizado
    const updatedTask = { ...task, id_situacao: newStatus };
    
    // Substitui o objeto antigo pelo novo no array. Esta é a correção principal.
    tasks.value.splice(taskIndex, 1, updatedTask);

    try {
      await api.put(`/tarefas/${taskId}`, { id_situacao: newStatus });
    } catch (err) {
      console.error("Falha ao atualizar o status da tarefa:", err);
      // Se a API falhar, reverte a alteração colocando o objeto original de volta
      tasks.value.splice(taskIndex, 1, originalTask);
    }
  }
};


const filterTasksByStatus = (status) => {
  return tasks.value.filter(task => (task.status === status || task.id_situacao === status));
};

// --- Funções para os Modais ---

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

const projectStatus = computed(() => statusMap[project.value?.id_situacao] || {});
const formatDate = (dateString) => {
  if (!dateString) return 'Não definida';
  return new Date(dateString).toLocaleDateString('pt-BR');
};
</script>

<template>
  <v-container fluid>
    <!-- Botão Voltar -->
    <v-btn variant="text" prepend-icon="mdi-arrow-left" @click="router.go(-1)" class="mb-6">Voltar</v-btn>

    <!-- Estados de Carregamento e Erro -->
    <div v-if="loading" class="text-center py-16">
      <v-progress-circular indeterminate color="green-darken-3" size="64"></v-progress-circular>
      <p class="mt-4 text-grey-darken-1">A carregar projeto...</p>
    </div>
    <v-alert v-else-if="error" type="error" variant="tonal">{{ error }}</v-alert>

    <div v-else-if="project">
      <!-- 1. HEADER DO PROJETO -->
      <v-card variant="tonal" color="grey-lighten-4" class="mb-8">
        <v-card-item class="pa-4">
          <div class="d-flex flex-wrap justify-space-between align-center">
            <div>
              <p class="text-overline text-grey-darken-1">Projeto</p>
              <h1 class="text-h4 font-weight-bold text-grey-darken-4">{{ project.titulo }}</h1>
            </div>
            <div class="d-flex align-center mt-2 mt-sm-0">
              <v-chip :color="project.id_situacao === 2 ? 'green' : 'orange'" label class="mr-4">
                Status: {{ project.id_situacao === 2 ? 'Aprovado' : 'Em Elaboração' }}
              </v-chip>
              <v-btn color="green-darken-3" variant="flat" @click="openCreateTaskModal" prepend-icon="mdi-plus">
                Adicionar Tarefa
              </v-btn>
            </div>
          </div>
        </v-card-item>
      </v-card>

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
            
            <div v-if="filterTasksByStatus(column.status).length === 0" class="text-center text-grey pa-4">
              Nenhuma tarefa aqui.
            </div>
            
            <v-card
              v-for="task in filterTasksByStatus(column.status)"
              :key="task.id_tarefa"
              class="mb-3 task-card"
              variant="flat"
              draggable="true"
              @dragstart="handleDragStart($event, task)"
            >
              <v-card-text class="font-weight-medium text-grey-darken-4">
                {{ task.detalhe || task.titulo }}
                <p v-if="task.descricao" class="text-caption font-weight-regular text-grey-darken-1 mt-1">{{ task.descricao }}</p>
              </v-card-text>
              <v-card-actions class="pa-1">
                <v-spacer></v-spacer>
                <v-btn icon="mdi-pencil" variant="text" size="x-small" @click="openEditTaskModal(task)"></v-btn>
              </v-card-actions>
            </v-card>
          </div>
        </v-col>
      </v-row>
    </div>

    <!-- 3. MODAL PARA CRIAR/EDITAR TAREFAS -->
    <CrudModal
      v-model="isTaskModalOpen"
      :title="taskModalConfig.title"
      :fields="taskModalConfig.fields"
      :item="currentTask"
      :loading="isTaskModalLoading"
      @save="handleSaveTask"
    />
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
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>
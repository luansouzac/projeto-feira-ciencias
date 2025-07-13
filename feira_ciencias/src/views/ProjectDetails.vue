<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../assets/plugins/axios.js'; // Ajuste o caminho para o seu arquivo de config do Axios
import CrudModal from '@/components/CrudModal.vue'; // 1. Importe o seu modal de CRUD

const route = useRoute();
const router = useRouter();

const project = ref(null);
const loading = ref(true);
const error = ref(null);

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

const statusMap = {
  1: { text: 'Em Elaboração', color: 'orange-darken-2', icon: 'mdi-pencil-ruler' },
  2: { text: 'Aprovado', color: 'green-darken-2', icon: 'mdi-check-decagram' },
  3: { text: 'Reprovado', color: 'red-darken-2', icon: 'mdi-close-octagon' },
};

onMounted(async () => {
  const projectId = route.params.id;
  try {
    const response = await api.get(`/projetos/${projectId}`);
    project.value = response.data;
  } catch (err) {
    console.error("Erro ao buscar detalhes do projeto:", err);
    error.value = "Não foi possível carregar os detalhes do projeto.";
  } finally {
    loading.value = false;
  }
});

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
    <v-btn variant="text" prepend-icon="mdi-arrow-left" @click="router.go(-1)" class="mb-6">Voltar</v-btn>

    <div v-if="loading" class="text-center py-16">
        <v-progress-circular indeterminate color="green-darken-3" size="64"></v-progress-circular>
        <p class="mt-4 text-grey-darken-1">A carregar detalhes do projeto...</p>
    </div>
    <v-alert v-else-if="error" type="error" variant="tonal" icon="mdi-alert-circle-outline">
        {{ error }}
    </v-alert>

    <v-row v-else-if="project" justify="center">
      <v-col cols="12" md="11" lg="9">
        <v-card class="mb-6">
          <v-card-title class="pa-4 d-flex flex-wrap justify-space-between align-center">
            <div>
              <h1 class="text-h4 font-weight-bold text-grey-darken-4">{{ project.titulo }}</h1>
              <p class="text-subtitle-1 text-grey-darken-1">Proposta de Projeto</p>
            </div>
            <v-chip :color="projectStatus.color" label size="large" class="mt-2 mt-sm-0">
              <v-icon start :icon="projectStatus.icon"></v-icon>
              {{ projectStatus.text }}
            </v-chip>
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text class="pa-4">
            <v-row>
              <v-col cols="12" md="8">
                <section class="mb-6">
                  <h2 class="text-h6 font-weight-medium text-grey-darken-3 mb-2">Problema a ser Resolvido</h2>
                  <p class="text-body-1" style="white-space: pre-wrap;">{{ project.problema }}</p>
                </section>
                <section>
                  <h2 class="text-h6 font-weight-medium text-grey-darken-3 mb-2">Relevância e Justificativa</h2>
                  <p class="text-body-1" style="white-space: pre-wrap;">{{ project.relevancia }}</p>
                </section>
              </v-col>
              <v-col cols="12" md="4">
                <v-card variant="tonal" color="grey-lighten-5">
                  <v-list-item>
                    <template v-slot:prepend><v-icon color="grey-darken-2">mdi-identifier</v-icon></template>
                    <v-list-item-title class="font-weight-bold text-grey-darken-4">ID do Projeto</v-list-item-title>
                    <v-list-item-subtitle class="text-grey-darken-4">{{ project.id_projeto }}</v-list-item-subtitle>
                  </v-list-item>
                  <v-divider></v-divider>
                  <v-list-item>
                    <template v-slot:prepend><v-icon color="grey-darken-2">mdi-account-circle-outline</v-icon></template>
                    <v-list-item-title class="font-weight-bold text-grey-darken-4">ID do Responsável</v-list-item-title>
                    <v-list-item-subtitle class="text-grey-darken-4">{{ project.id_responsavel }}</v-list-item-subtitle>
                  </v-list-item>
                  <v-divider></v-divider>
                  <v-list-item>
                    <template v-slot:prepend><v-icon color="grey-darken-2">mdi-calendar-plus</v-icon></template>
                    <v-list-item-title class="font-weight-bold text-grey-darken-4">Data de Criação</v-list-item-title>
                    <v-list-item-subtitle class="text-grey-darken-4">{{ formatDate(project.created_at || project.data_criacao) }}</v-list-item-subtitle>
                  </v-list-item>
                  <v-divider></v-divider>
                  <v-list-item>
                    <template v-slot:prepend><v-icon color="grey-darken-2">mdi-calendar-check</v-icon></template>
                    <v-list-item-title class="font-weight-bold text-grey-darken-4">Data de Aprovação</v-list-item-title>
                    <v-list-item-subtitle class="text-grey-darken-4">{{ formatDate(project.data_aprovacao) }}</v-list-item-subtitle>
                  </v-list-item>
                </v-card>
              </v-col>
            </v-row>
          </v-card-text>
          <v-divider></v-divider>
          <v-card-actions class="pa-4">
            <v-btn color="green-darken-3" variant="flat" prepend-icon="mdi-pencil" @click="openEditModal">Editar Projeto</v-btn>
            <v-btn color="red-darken-3" variant="tonal" prepend-icon="mdi-delete-outline" @click="openDeleteModal">Excluir</v-btn>
          </v-card-actions>
        </v-card>
        <v-row>
          <v-col cols="12" md="6">
            <v-card>
              <v-card-title>Linha do Tempo</v-card-title>
              <v-card-text class="text-center text-grey">
                <v-icon size="48" class="mb-2">mdi-timeline-text-outline</v-icon>
                <p>A linha do tempo do projeto aparecerá aqui.</p>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="6">
            <v-card>
              <v-card-title>Galeria e Arquivos</v-card-title>
              <v-card-text class="text-center text-grey">
                <v-icon size="48" class="mb-2">mdi-image-multiple-outline</v-icon>
                <p>Fotos e documentos do projeto aparecerão aqui.</p>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-col>
    </v-row>

  
    <CrudModal
      v-if="project"
      v-model="isEditModalOpen"
      :title="modalConfig.title"
      :fields="modalConfig.fields"
      :item="project"
      :loading="isModalLoading"
      @save="handleUpdate"
    />

    <!-- Modal de Confirmação de Exclusão (um v-dialog simples) -->
    <v-dialog v-model="isDeleteModalOpen" max-width="500px" persistent>
      <v-card>
        <v-card-title class="text-h5">Confirmar Exclusão</v-card-title>
        <v-card-text>
          Tem a certeza de que deseja excluir o projeto <strong>"{{ project?.titulo }}"</strong>? Esta ação não pode ser desfeita.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey-darken-1" variant="text" @click="isDeleteModalOpen = false" :disabled="isModalLoading">Cancelar</v-btn>
          <v-btn color="red-darken-3" variant="flat" @click="handleDelete" :loading="isModalLoading">Excluir</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

  </v-container>
</template>

<style scoped>
.text-body-1 {
  line-height: 1.7;
}
</style>

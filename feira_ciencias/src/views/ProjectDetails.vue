<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../assets/plugins/axios.js'; 

const route = useRoute();
const router = useRouter();

const project = ref(null);
const loading = ref(true);
const error = ref(null);

// --- Estado do Modal ---
const isModalOpen = ref(false);
const isModalLoading = ref(false);
const currentItem = ref(null); // Guarda o item para edição (null para criação)

const timelineEvents = ref([]);
const galleryItems = ref([]);
const evaluatorComments = ref([]);


const statusMap = {
  1: { text: 'Em Elaboração', color: 'orange-darken-2', icon: 'mdi-pencil-ruler' },
  2: { text: 'Aprovado', color: 'green-darken-2', icon: 'mdi-check-decagram' },
  3: { text: 'Reprovado', color: 'red-darken-2', icon: 'mdi-close-octagon' },
};

onMounted(async () => {
  const projectId = route.params.id;

  if (!projectId) {
    error.value = "ID do projeto não fornecido na URL.";
    loading.value = false;
    return;
  }

  try {
    const response = await api.get(`/projetos/${projectId}`);
    project.value = response.data;
    
    //CHAMADAS ADICIONAIS, TAREFAS, ARQUIVOS E ETC
    
  } catch (err) {
    console.error("Erro ao buscar detalhes do projeto:", err);
    if (err.response && err.response.status === 404) {
      error.value = "Projeto não encontrado.";
    } else {
      error.value = "Não foi possível carregar os detalhes do projeto.";
    }
  } finally {
    loading.value = false;
  }
});

const projectStatus = computed(() => {
  if (!project.value) return { text: 'Desconhecido', color: 'grey', icon: 'mdi-help-circle' };
  return statusMap[project.value.id_situacao] || { text: 'Desconhecido', color: 'grey', icon: 'mdi-help-circle' };
});

const formatDate = (dateString) => {
  if (!dateString) return 'Não definida';
  return new Date(dateString).toLocaleDateString('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric'
  });
};
</script>

<template>
  <v-container fluid>
    <!-- Botão para voltar para a página anterior -->
    <v-btn variant="text" prepend-icon="mdi-arrow-left" @click="router.go(-1)" class="mb-6">
      Voltar
    </v-btn>

    <!-- Estado de Carregamento -->
    <div v-if="loading" class="text-center py-16">
      <v-progress-circular indeterminate color="green-darken-3" size="64"></v-progress-circular>
      <p class="mt-4 text-grey-darken-1">Carregando detalhes do projeto...</p>
    </div>

    <!-- Estado de Erro -->
    <v-alert v-else-if="error" type="error" variant="tonal" icon="mdi-alert-circle-outline" class="mx-auto" max-width="700px">
      {{ error }}
    </v-alert>

    <!-- Conteúdo Principal -->
    <v-row v-else-if="project" justify="center">
      <v-col cols="12" md="11" lg="9">
        
        <!-- Card Principal com os Detalhes do Projeto -->
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
              <!-- Coluna de Descrição -->
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
              <!-- Coluna de Metadados -->
              <v-col cols="12" md="4">
                <v-card variant="tonal" color="grey-lighten-5">
                  <v-list-item>
                    <template v-slot:prepend><v-icon color="grey-darken-2">mdi-identifier</v-icon></template>
                    <v-list-item-title class="font-weight-bold text-grey-darken-3">ID do Projeto</v-list-item-title>
                    <v-list-item-subtitle class="text-grey-darken-3">{{ project.id_projeto }}</v-list-item-subtitle>
                  </v-list-item>
                  <v-divider></v-divider>
                  <v-list-item>
                    <template v-slot:prepend><v-icon color="grey-darken-2">mdi-account-circle-outline</v-icon></template>
                    <v-list-item-title class="font-weight-bold text-grey-darken-3">ID do Responsável</v-list-item-title>
                    <v-list-item-subtitle class="text-grey-darken-3">{{ project.id_responsavel }}</v-list-item-subtitle>
                  </v-list-item>
                  <v-divider></v-divider>
                  <v-list-item>
                    <template v-slot:prepend><v-icon color="grey-darken-2">mdi-calendar-plus</v-icon></template>
                    <v-list-item-title class="font-weight-bold text-grey-darken-3">Data de Criação</v-list-item-title>
                    <v-list-item-subtitle class="text-grey-darken-3">{{ formatDate(project.created_at || project.data_criacao) }}</v-list-item-subtitle>
                  </v-list-item>
                  <v-divider></v-divider>
                  <v-list-item>
                    <template v-slot:prepend><v-icon color="grey-darken-2">mdi-calendar-check</v-icon></template>
                    <v-list-item-title class="font-weight-bold text-grey-darken-3">Data de Aprovação</v-list-item-title>
                    <v-list-item-subtitle class="text-grey-darken-3">{{ formatDate(project.data_aprovacao) }}</v-list-item-subtitle>
                  </v-list-item>
                </v-card>
              </v-col>
            </v-row>
          </v-card-text>
          <v-divider></v-divider>
          <v-card-actions class="pa-4">
            <v-btn color="green-darken-3" variant="flat" prepend-icon="mdi-pencil">Editar Projeto</v-btn>
            <v-btn color="red-darken-3" variant="tonal" prepend-icon="mdi-delete-outline">Excluir</v-btn>
          </v-card-actions>
        </v-card>

        <!-- Seções Adicionais (Linha do Tempo, Galeria, etc.) -->
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
  </v-container>
</template>

<style scoped>
.text-body-1 {
  line-height: 1.7;
}
</style>

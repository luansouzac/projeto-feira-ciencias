<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../assets/plugins/axios.js';
import ProjectCard from '@/components/ProjectCard.vue';

const router = useRouter();

// --- ESTADOS DA PÁGINA ---
const projetos = ref([]); // Renomeado de projetosAprovados para consistência
const carregando = ref(true);
const erro = ref(null);
const filtroBusca = ref('');
const viewMode = ref('grid'); // 'grid' ou 'list'
let userId = null;

// --- OBTÉM DADOS DO USUÁRIO LOGADO ---
const userDataString = sessionStorage.getItem('user_data');
if (userDataString) {
  const userData = JSON.parse(userDataString);
  userId = userData.user.id_usuario;
}

// --- LÓGICA DE BUSCA DE DADOS ---
onMounted(async () => {
  if (!userId) {
    erro.value = "Usuário não encontrado. Faça o login para ver seus projetos aprovados.";
    carregando.value = false;
    return;
  }

  carregando.value = true;
  try {
    const url = `/projetos?id_responsavel=${userId}&id_situacao=2`;
    const { data } = await api.get(url);
    projetos.value = data;
  } catch (err) {
    console.error("Erro ao buscar projetos aprovados do usuário:", err);
    erro.value = "Não foi possível carregar seus projetos aprovados.";
  } finally {
    carregando.value = false;
  }
});

// --- DADOS FILTRADOS PARA AS VISUALIZAÇÕES ---
const projetosFiltrados = computed(() => {
  if (!filtroBusca.value) {
    return projetos.value;
  }
  return projetos.value.filter(p =>
    p.titulo.toLowerCase().includes(filtroBusca.value.toLowerCase())
  );
});

// --- FUNÇÃO DE NAVEGAÇÃO ---
function goToProjectDetails(id) {
  // A rota original era /projetos/:id, mantive assim.
  // Se precisar ser /projetos/orientados/:id, basta ajustar aqui.
  router.push(`/projetos/${id}`);
}
</script>

<template>
  <v-container fluid>
    <!-- BOTÃO VOLTAR E CABEÇALHO -->
    <v-btn variant="text" prepend-icon="mdi-arrow-left" @click="router.go(-1)" class="mb-6">
      Voltar
    </v-btn>
    <v-row class="mb-6">
      <v-col>
        <h1 class="text-h4 font-weight-bold text-grey-darken-4">Galeria de Projetos Inscritos</h1>
        <p class="text-subtitle-1 text-grey-darken-1">
          Explore os projetos você está inscrito.
        </p>
      </v-col>
    </v-row>

    <!-- FILTROS E SELETOR DE VISUALIZAÇÃO -->
    <v-card class="mb-8 pa-4" variant="outlined">
      <v-row align="center" no-gutters>
        <v-col cols="12" md="8">
          <v-text-field
            v-model="filtroBusca"
            label="Buscar por título do projeto..."
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
            density="compact"
            hide-details
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="4" class="d-flex justify-start justify-md-end mt-4 mt-md-0">
          <v-btn-toggle v-model="viewMode" mandatory density="compact" variant="outlined">
            <v-btn value="grid" aria-label="Visualização em Grade">
              <v-icon>mdi-view-grid-outline</v-icon>
              <v-tooltip activator="parent" location="bottom">Grade</v-tooltip>
            </v-btn>
            <v-btn value="list" aria-label="Visualização em Lista">
              <v-icon>mdi-view-list-outline</v-icon>
              <v-tooltip activator="parent" location="bottom">Lista</v-tooltip>
            </v-btn>
          </v-btn-toggle>
        </v-col>
      </v-row>
    </v-card>

    <!-- ESTADO DE CARREGAMENTO -->
    <v-row v-if="carregando">
      <v-col v-for="n in 6" :key="n" cols="12" sm="6" lg="4">
        <v-skeleton-loader type="image, article, actions"></v-skeleton-loader>
      </v-col>
    </v-row>

    <!-- ESTADO DE ERRO -->
    <v-alert v-else-if="erro" type="error" border="start" variant="tonal">
      {{ erro }}
    </v-alert>

    <!-- ESTADO VAZIO -->
    <div v-else-if="projetosFiltrados.length === 0">
        <v-card flat border class="text-center pa-8">
          <v-icon size="60" class="mb-4 text-grey-lighten-1">mdi-folder-search-outline</v-icon>
          <p class="text-grey-darken-1">
            {{ filtroBusca ? 'Nenhum projeto encontrado para sua busca.' : 'Nenhum projeto aprovado foi encontrado no momento.' }}
          </p>
        </v-card>
    </div>
    
    <!-- CONTEÚDO PRINCIPAL -->
    <div v-else>
      <!-- VISUALIZAÇÃO EM GRADE -->
      <v-row v-if="viewMode === 'grid'">
        <v-col
          v-for="projeto in projetosFiltrados"
          :key="projeto.id_projeto"
          cols="12" sm="6" lg="4"
        >
          <ProjectCard
            :projeto="projeto"
            @ver-detalhes="goToProjectDetails(projeto.id_projeto)"
          />
        </v-col>
      </v-row>

      <!-- VISUALIZAÇÃO EM LISTA -->
      <v-card v-else-if="viewMode === 'list'" variant="outlined">
        <v-table hover>
          <thead>
            <tr>
              <th class="text-left">Projeto</th>
              <th class="text-left d-none d-sm-table-cell">Orientador</th>
              <th class="text-left d-none d-md-table-cell">Membros</th>
              <th class="text-right">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="projeto in projetosFiltrados" :key="`list-${projeto.id_projeto}`" @click="goToProjectDetails(projeto.id_projeto)" style="cursor: pointer;">
              <td>
                <div class="font-weight-bold">{{ projeto.titulo }}</div>
                <div class="text-caption text-grey-darken-1">{{ projeto.area_conhecimento || 'Área não definida' }}</div>
              </td>
              <td class="d-none d-sm-table-cell">{{ projeto.orientador?.nome || 'Não definido' }}</td>
              <td class="d-none d-md-table-cell">{{ projeto.membros_count || 0 }}</td>
              <td class="text-right">
                <v-btn color="grey-darken-2" variant="text" size="small">
                  Ver detalhes <v-icon end>mdi-arrow-right</v-icon>
                </v-btn>
              </td>
            </tr>
          </tbody>
        </v-table>
      </v-card>
    </div>
  </v-container>
</template>

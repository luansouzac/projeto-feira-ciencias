<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../assets/plugins/axios.js'
import ProjectCard from '@/components/ProjectCard.vue'

const router = useRouter()

// O userId aqui é ESSENCIAL
const userId = ref(null)
const userDataString = sessionStorage.getItem('user_data');
if (userDataString) {
  const userData = JSON.parse(userDataString);
  userId.value = userData.user.id_usuario;
}

// Estado da página
const projetosAprovados = ref([])
const carregando = ref(true)
const erro = ref(null)

onMounted(async () => {
  // Adicionamos uma verificação para não fazer a chamada se não houver usuário
  if (!userId.value) {
    erro.value = "Usuário não encontrado. Faça o login para ver seus projetos aprovados.";
    carregando.value = false;
    return;
  }

  carregando.value = true;
  try {
    // A MUDANÇA ESTÁ AQUI! Combinamos os dois filtros que sua API agora suporta.
    const url = `/projetos?id_responsavel=${userId.value}&id_situacao=2`;
    const { data } = await api.get(url);
    projetosAprovados.value = data;
  } catch (err) {
    console.error("Erro ao buscar projetos aprovados do usuário:", err);
    erro.value = "Não foi possível carregar seus projetos aprovados.";
  } finally {
    carregando.value = false;
  }
})

function goToProjectDetails(id) {
  router.push(`/projetos/${id}`)
}
</script>

<template>
  <v-container fluid>
    <v-row class="mb-6">
      <v-col>
        <h1 class="text-h4 font-weight-bold text-grey-darken-4">Galeria de Projetos</h1>
        <p class="text-subtitle-1 text-grey-darken-1">
          Explore os projetos que foram aprovados e se destacaram.
        </p>
      </v-col>
    </v-row>

    <v-row v-if="carregando">
      <v-col v-for="n in 6" :key="n" cols="12" sm="6" lg="4">
        <v-skeleton-loader type="image, article, actions"></v-skeleton-loader>
      </v-col>
    </v-row>

    <v-row v-else-if="erro">
      <v-col>
        <v-alert type="error" border="start" variant="tonal">
          {{ erro }}
        </v-alert>
      </v-col>
    </v-row>

    <v-row v-else-if="projetosAprovados.length === 0">
      <v-col>
        <v-card flat border class="text-center pa-8">
          <v-icon size="60" class="mb-4 text-grey-lighten-1">mdi-folder-search-outline</v-icon>
          <p class="text-grey-darken-1">Nenhum projeto aprovado foi encontrado no momento.</p>
        </v-card>
      </v-col>
    </v-row>

    <v-row v-else>
      <v-col
        v-for="projeto in projetosAprovados"
        :key="projeto.id_projeto"
        cols="12"
        sm="6"
        lg="4"
      >
        <ProjectCard
          :projeto="projeto"
          @ver-detalhes="goToProjectDetails"
        >
          </ProjectCard>
      </v-col>
    </v-row>
  </v-container>
</template>
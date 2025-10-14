<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../assets/plugins/axios.js';

const route = useRoute();
const router = useRouter();
const evento = ref(null);
const projetos = ref([]);
const carregando = ref(true);
const erro = ref(null);

onMounted(async () => {
  const eventoId = route.params.id;
  try {
    const response = await api.get(`/public/eventos/${eventoId}/projetos`);
    evento.value = response.data.evento;
    projetos.value = response.data.projetos;
  } catch (err) {
    console.error("Erro ao buscar dados públicos do evento:", err);
    erro.value = "Não foi possível carregar os projetos deste evento.";
  } finally {
    carregando.value = false;
  }
});

const goToProject = (projetoId) => {
    router.push(`/public/projeto/${projetoId}`);
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR', {
        timeZone: 'UTC', 
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
};
</script>

<template>
  <div class="bg-grey-lighten-5">
    <v-container class="py-10">
      <div v-if="carregando" class="text-center py-16">
        <v-progress-circular indeterminate color="green-darken-3" size="64"></v-progress-circular>
        <p class="mt-4 text-grey-darken-1">A carregar evento...</p>
      </div>
      
      <v-alert v-else-if="erro" type="error" variant="tonal" prominent>{{ erro }}</v-alert>

      <div v-else-if="evento">
        <v-row justify="center" class="mb-12">
            <v-col cols="12" md="10">
                <v-card color="green-darken-4" theme="dark" class="pa-4 text-center">
                    <v-icon size="48" class="mb-2">mdi-calendar-star</v-icon>
                    <h1 class="text-h3 font-weight-bold">{{ evento.nome }}</h1>
                    <p v-if="evento.data_evento" class="text-h6 font-weight-light mt-1">
                      {{ formatDate(evento.data_evento) }}
                    </p>
                </v-card>
            </v-col>
        </v-row>

        <v-row v-if="projetos.length > 0">
          <v-col v-for="projeto in projetos" :key="projeto.id_projeto" cols="12" sm="6" lg="4">
            <v-card @click="goToProject(projeto.id_projeto)" hover class="d-flex flex-column fill-height">
              <v-card-item>
                <v-card-title class="text-wrap font-weight-bold text-grey-darken-4">{{ projeto.titulo }}</v-card-title>
                <v-card-subtitle>
                    <v-icon size="small" start>mdi-account-tie-outline</v-icon>
                    Orientador(a): {{ projeto.orientador.nome }}
                </v-card-subtitle>
              </v-card-item>
              <v-card-text class="text-medium-emphasis flex-grow-1">
                {{ projeto.problema.substring(0, 120) }}{{ projeto.problema.length > 120 ? '...' : '' }}
              </v-card-text>
              <v-divider></v-divider>
              <v-card-actions class="justify-end pa-3">
                <v-btn color="green-darken-3" variant="text">
                  Ver Detalhes
                  <v-icon end>mdi-arrow-right</v-icon>
                </v-btn>
              </v-card-actions>
            </v-card>
          </v-col>
        </v-row>
        
        <div v-else>
            <v-card flat border class="text-center pa-8">
                <v-icon size="60" class="mb-4 text-grey-lighten-1">mdi-folder-search-outline</v-icon>
                <p class="text-h6 text-grey-darken-2">Nenhum projeto publicado</p>
                <p class="text-grey-darken-1">Ainda não há projetos disponíveis para visualização neste evento.</p>
            </v-card>
        </div>
      </div>
    </v-container>
  </div>
</template>

<style scoped>
.text-wrap {
    white-space: normal;
    line-height: 1.3;
}
.fill-height {
    height: 100%;
}
</style>


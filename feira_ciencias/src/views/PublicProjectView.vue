<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import api from '../assets/plugins/axios.js';

const route = useRoute();
const projeto = ref(null);
const carregando = ref(true);
const erro = ref(null);

onMounted(async () => {
  const projetoId = route.params.id;
  try {
    const response = await api.get(`/public/projetos/${projetoId}`);
    projeto.value = response.data;
  } catch (err) {
    console.error("Erro ao buscar dados públicos do projeto:", err);
    erro.value = err.response?.data?.erro || "Não foi possível carregar o projeto.";
  } finally {
    carregando.value = false;
  }
});

const membrosDaEquipe = computed(() => {
    return projeto.value?.equipe?.membro_equipe || [];
});

const getInitials = (name) => {
    if (!name) return '?';
    const names = name.split(' ');
    if (names.length > 1) {
        return `${names[0][0]}${names[names.length - 1][0]}`.toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

const formatDate = (dateString) => {
    if (!dateString) return 'Data não definida';
    return new Date(dateString).toLocaleDateString('pt-BR', { timeZone: 'UTC' });
};
</script>

<template>
  <div class="bg-grey-lighten-5">
    <v-container class="py-10">
      <div v-if="carregando" class="text-center py-16">
        <v-progress-circular indeterminate color="green-darken-3" size="64"></v-progress-circular>
        <p class="mt-4 text-grey-darken-1">A carregar projeto...</p>
      </div>
      <v-alert v-else-if="erro" type="error" variant="tonal" prominent>{{ erro }}</v-alert>
      
      <div v-else-if="projeto">
        <v-row justify="center" class="text-center mb-12">
          <v-col cols="12" md="10">
            <div class="d-flex align-center justify-center mb-2">
              <v-icon color="green-darken-2" class="mr-2">mdi-calendar-star</v-icon>
              <p class="text-overline text-green-darken-2">
                Evento: {{ projeto.eventos?.nome }}
              </p>
            </div>
            <h1 class="text-h2 font-weight-bold text-green-darken-4" style="line-height: 1.2;">
              {{ projeto.titulo }}
            </h1>
          </v-col>
        </v-row>
        
        <v-row justify="center">
          <v-col cols="12" md="9">
            <v-card class="mb-8" variant="flat" border>
              <v-card-text class="pa-6">
                <div class="mb-6">
                  <h2 class="text-h6 font-weight-medium text-grey-darken-3 d-flex align-center mb-2">
                    <v-icon start color="grey-darken-1">mdi-lightbulb-on-outline</v-icon>
                    O Problema
                  </h2>
                  <p class="text-body-1 text-medium-emphasis">{{ projeto.problema }}</p>
                </div>
                <v-divider></v-divider>
                <div class="mt-6">
                  <h2 class="text-h6 font-weight-medium text-grey-darken-3 d-flex align-center mb-2">
                    <v-icon start color="grey-darken-1">mdi-bullseye-arrow</v-icon>
                    Relevância
                  </h2>
                  <p class="text-body-1 text-medium-emphasis">{{ projeto.relevancia }}</p>
                </div>
              </v-card-text>
            </v-card>

            <v-row>
              <v-col cols="12" md="6">
                <v-card height="100%" variant="outlined">
                  <v-card-item>
                    <v-card-title>
                      <v-icon start>mdi-account-group-outline</v-icon>
                      Equipa do Projeto
                    </v-card-title>
                  </v-card-item>
                  <v-list class="bg-transparent">
                     <div v-if="membrosDaEquipe.length === 0" class="text-center text-grey pa-4">
                       Nenhum membro na equipa ainda.
                     </div>
                    <v-list-item v-for="membro in membrosDaEquipe" :key="membro.id_membro" :title="membro.usuario?.nome">
                      <template v-slot:prepend>
                        <v-avatar color="green-lighten-1">
                          <span class="text-white font-weight-bold">{{ getInitials(membro.usuario?.nome) }}</span>
                        </v-avatar>
                      </template>
                    </v-list-item>
                  </v-list>
                </v-card>
              </v-col>
              <v-col cols="12" md="6">
                <v-card height="100%" variant="outlined">
                   <v-card-item>
                    <v-card-title>
                      <v-icon start>mdi-account-tie-outline</v-icon>
                      Orientação
                    </v-card-title>
                  </v-card-item>
                  <v-list class="bg-transparent">
                    <v-list-item :title="projeto.orientador?.nome || 'Não definido'" subtitle="Orientador(a)">
                       <template v-slot:prepend>
                        <v-avatar color="blue-grey-lighten-1">
                          <v-icon color="white">mdi-school-outline</v-icon>
                        </v-avatar>
                      </template>
                    </v-list-item>
                    <v-list-item v-if="projeto.coorientador" :title="projeto.coorientador?.nome || 'Não definido'" subtitle="Coorientador(a)">
                       <template v-slot:prepend>
                        <v-avatar color="blue-grey-lighten-2">
                           <v-icon color="white">mdi-school-outline</v-icon>
                        </v-avatar>
                      </template>
                    </v-list-item>
                  </v-list>
                </v-card>
              </v-col>
            </v-row>
          </v-col>
        </v-row>
      </div>
    </v-container>
  </div>
</template>


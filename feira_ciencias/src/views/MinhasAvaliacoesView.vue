<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/assets/plugins/axios.js';
import { useNotificationStore } from '@/stores/notification';

// --- Instâncias e Stores ---
const router = useRouter();
const notificationStore = useNotificationStore();

// --- Estado da Página ---
const atribuicoes = ref([]);
const loading = ref(true);
const erro = ref(null);
const searchQuery = ref('');
const statusFilter = ref('pendente'); // Começa a mostrar os pendentes por defeito

// --- Busca de Dados ---
onMounted(async () => {
  try {
    const response = await api.get('/minhas-avaliacoes');
    atribuicoes.value = response.data;
  } catch (err) {
    console.error("Erro ao buscar avaliações atribuídas:", err);
    erro.value = "Não foi possível carregar as suas avaliações.";
    notificationStore.showError(erro.value);
  } finally {
    loading.value = false;
  }
});

// --- Propriedades Computadas ---
const filteredAtribuicoes = computed(() => {
  return atribuicoes.value.filter(atribuicao => {
    const correspondeStatus = atribuicao.status === statusFilter.value;
    const correspondeBusca = atribuicao.projeto.titulo.toLowerCase().includes(searchQuery.value.toLowerCase());
    return correspondeStatus && correspondeBusca;
  });
});

const totalPendentes = computed(() => atribuicoes.value.filter(a => a.status === 'pendente').length);
const totalConcluidas = computed(() => atribuicoes.value.filter(a => a.status === 'concluida').length);

// --- Métodos ---
const goToAvaliacao = (projetoId) => {
  router.push(`/projeto/${projetoId}/avaliar`);
};
</script>

<template>
  <v-container>
    <div v-if="loading" class="text-center py-16">
      <v-progress-circular indeterminate color="green-darken-3" size="64" />
      <p class="mt-4 text-grey-darken-1">A carregar as suas avaliações...</p>
    </div>
    <v-alert v-else-if="erro" type="error" variant="tonal" prominent>{{ erro }}</v-alert>

    <div v-else>
      <h1 class="text-h4 font-weight-bold mb-2">Minhas Avaliações</h1>
      <p class="text-medium-emphasis mb-8">Projetos que foram atribuídos a você para avaliação.</p>

      <!-- Cartões de Resumo -->
      <v-row class="mb-6">
        <v-col cols="12" sm="6">
          <v-card variant="tonal" color="orange">
            <v-card-text class="d-flex align-center">
              <v-icon size="40" class="mr-4">mdi-clipboard-clock-outline</v-icon>
              <div>
                <div class="text-h4 font-weight-bold">{{ totalPendentes }}</div>
                <div class="text-overline">Avaliações Pendentes</div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6">
          <v-card variant="tonal" color="green">
             <v-card-text class="d-flex align-center">
              <v-icon size="40" class="mr-4">mdi-clipboard-check-outline</v-icon>
              <div>
                <div class="text-h4 font-weight-bold">{{ totalConcluidas }}</div>
                <div class="text-overline">Avaliações Concluídas</div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Filtros e Pesquisa -->
      <v-card class="mb-8 pa-4" variant="outlined">
        <v-row align="center">
          <v-col cols="12" md="6">
            <v-text-field
              v-model="searchQuery"
              label="Pesquisar por título do projeto..."
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              hide-details
            ></v-text-field>
          </v-col>
          <v-col cols="12" md="6">
            <v-chip-group
              v-model="statusFilter"
              mandatory
              color="green-darken-3"
              class="justify-md-end"
            >
              <v-chip value="pendente" filter>Pendentes</v-chip>
              <v-chip value="concluida" filter>Concluídas</v-chip>
            </v-chip-group>
          </v-col>
        </v-row>
      </v-card>

      <!-- Lista de Projetos para Avaliar -->
      <div v-if="filteredAtribuicoes.length > 0">
        <v-card 
          v-for="atribuicao in filteredAtribuicoes" 
          :key="atribuicao.id" 
          class="mb-4"
          variant="outlined"
          :disabled="atribuicao.status === 'concluida'"
        >
          <v-card-item>
            <div>
              <div class="text-overline mb-1 text-medium-emphasis">
                {{ atribuicao.projeto.eventos?.nome || 'Evento não especificado' }}
              </div>
              <div class="text-h6 mb-1">{{ atribuicao.projeto.titulo }}</div>
            </div>
          </v-card-item>
          <v-card-actions class="pa-4">
             <v-chip
                :color="atribuicao.status === 'pendente' ? 'orange' : 'green'"
                :prepend-icon="atribuicao.status === 'pendente' ? 'mdi-clock-outline' : 'mdi-check-circle-outline'"
                label
                variant="tonal"
              >
                {{ atribuicao.status === 'pendente' ? 'Pendente' : 'Concluída' }}
              </v-chip>
            <v-spacer></v-spacer>
            <v-btn
              v-if="atribuicao.status === 'pendente'"
              color="green-darken-3"
              variant="flat"
              @click="goToAvaliacao(atribuicao.projeto.id_projeto)"
            >
              Avaliar Agora
              <v-icon end>mdi-arrow-right</v-icon>
            </v-btn>
             <v-btn
              v-else
              color="grey"
              variant="text"
              disabled
            >
              Avaliação Submetida
            </v-btn>
          </v-card-actions>
        </v-card>
      </div>
      <v-alert v-else type="info" variant="tonal">
        Nenhuma avaliação encontrada com os filtros atuais.
      </v-alert>

    </div>
  </v-container>
</template>

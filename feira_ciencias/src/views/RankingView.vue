<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/assets/plugins/axios.js';
import { useNotificationStore } from '@/stores/notification';

// --- Instâncias e Stores ---
const router = useRouter();
const notificationStore = useNotificationStore();

// --- Estado da Página ---
const projetosRanqueados = ref([]);
const loading = ref(true);
const erro = ref(null);
const searchQuery = ref('');

// --- Busca de Dados ---
onMounted(async () => {
  try {
    const response = await api.get('/projetos/resultados-gerais');
    projetosRanqueados.value = response.data;
  } catch (err) {
    console.error("Erro ao buscar o ranking de projetos:", err);
    erro.value = "Não foi possível carregar os resultados.";
    notificationStore.showError(erro.value);
  } finally {
    loading.value = false;
  }
});

// --- Propriedades Computadas ---
const filteredRanking = computed(() => {
  if (!searchQuery.value) {
    return projetosRanqueados.value;
  }
  return projetosRanqueados.value.filter(projeto =>
    projeto.titulo.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

// --- Métodos ---
const goToResults = (projetoId) => {
  router.push(`/projetos/${projetoId}/resultados`);
};

const formatNota = (nota) => {
    console.log('Valor recebido por formatNota:', nota);

    const num = parseFloat(nota);

    if (isNaN(num)) {
        return '0.00';
    }

    return num.toFixed(2);
}
</script>

<template>
  <v-container>
    <div v-if="loading" class="text-center py-16">
      <v-progress-circular indeterminate color="green-darken-3" size="64" />
      <p class="mt-4 text-grey-darken-1">A carregar ranking dos projetos...</p>
    </div>
    <v-alert v-else-if="erro" type="error" variant="tonal" prominent>{{ erro }}</v-alert>

    <div v-else>
      <h1 class="text-h4 font-weight-bold mb-2">Resultados Gerais dos Projetos</h1>
      <p class="text-medium-emphasis mb-8">Veja o ranking dos projetos com base nas avaliações oficiais.</p>

      <v-card variant="outlined">
        <v-card-title class="d-flex flex-wrap align-center pa-4">
          <span class="text-h6">Ranking da Feira</span>
          <v-spacer></v-spacer>
          <v-text-field
            v-model="searchQuery"
            label="Pesquisar projeto..."
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
            density="compact"
            hide-details
            clearable
            style="max-width: 300px;"
          ></v-text-field>
        </v-card-title>
        
        <v-divider></v-divider>

        <v-table hover>
          <thead>
            <tr>
              <th class="text-left" style="width: 80px;">Posição</th>
              <th class="text-left">Projeto</th>
              <th class="text-left d-none d-sm-table-cell">Evento</th>
              <th class="text-center">Nota Média</th>
              <th class="text-right">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(projeto, index) in filteredRanking" :key="projeto.id_projeto">
              <td>
                <v-chip
                  :color="index < 3 ? 'green-darken-3' : 'grey-lighten-1'"
                  class="font-weight-bold"
                  :variant="index < 3 ? 'flat' : 'outlined'"
                >
                  <v-icon v-if="index < 3" start>mdi-trophy-variant-outline</v-icon>
                  {{ index + 1 }}º
                </v-chip>
              </td>
              <td>
                <div class="font-weight-bold">{{ projeto.titulo }}</div>
              </td>
              <td class="d-none d-sm-table-cell">{{ projeto.eventos?.nome || 'N/A' }}</td>
              <td class="text-center">
                <span class="font-weight-bold text-h6 text-green-darken-2">
                  {{ formatNota(projeto.avaliacoes_avg_nota_geral) }}
                </span>
              </td>
              <td class="text-right">
                <v-btn
                  color="blue-darken-2"
                  variant="text"
                  @click="goToResults(projeto.id_projeto)"
                >
                  Ver Detalhes
                </v-btn>
              </td>
            </tr>
          </tbody>
        </v-table>
         <div v-if="filteredRanking.length === 0" class="text-center text-grey py-8">
            Nenhum projeto encontrado.
        </div>
      </v-card>
    </div>
  </v-container>
</template>


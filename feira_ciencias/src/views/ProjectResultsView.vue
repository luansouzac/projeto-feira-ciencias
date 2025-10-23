<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import api from '@/assets/plugins/axios.js';
import { useNotificationStore } from '@/stores/notification';

// --- Instâncias e Stores ---
const route = useRoute();
const notificationStore = useNotificationStore();

// --- Estado da Página ---
const projeto = ref(null);
const avaliacoes = ref([]);
const loading = ref(true);
const erro = ref(null);

// --- Busca de Dados ---
onMounted(async () => {
  const projetoId = route.params.id;
  try {
    const [projetoResponse, avaliacoesResponse] = await Promise.all([
      api.get(`/projetos/${projetoId}`),
      api.get(`/projetos/${projetoId}/avaliacoes`)
    ]);

    projeto.value = projetoResponse.data;
    avaliacoes.value = avaliacoesResponse.data;

  } catch (err) {
    console.error("Erro ao buscar resultados do projeto:", err);
    erro.value = "Não foi possível carregar os resultados desta avaliação.";
    notificationStore.showError(erro.value);
  } finally {
    loading.value = false;
  }
});

// --- Propriedades Computadas para Cálculos ---

const notaMediaGeral = computed(() => {
  if (!avaliacoes.value || avaliacoes.value.length === 0) return 0;
  const total = avaliacoes.value.reduce((sum, avaliacao) => sum + parseFloat(avaliacao.nota_geral), 0);
  return (total / avaliacoes.value.length).toFixed(2);
});

const notasPorCriterio = computed(() => {
  const criterios = {};
  if (!avaliacoes.value) return [];

  const todasRespostas = avaliacoes.value.flatMap(a => a.respostas);

  todasRespostas.forEach(resposta => {
    const criterioNome = resposta.pergunta.criterio;
    if (!criterios[criterioNome]) {
      criterios[criterioNome] = { total: 0, count: 0 };
    }
    criterios[criterioNome].total += resposta.valor_resposta;
    criterios[criterioNome].count++;
  });

  return Object.keys(criterios).map(nome => ({
    nome,
    media: (criterios[nome].total / criterios[nome].count).toFixed(2)
  }));
});

</script>

<template>
  <div class="bg-grey-lighten-5 fill-height">
    <v-container class="py-10">
      <div v-if="loading" class="text-center py-16">
        <v-progress-circular indeterminate color="green-darken-3" size="64" />
        <p class="mt-4 text-grey-darken-1">A carregar resultados...</p>
      </div>
      <v-alert v-else-if="erro" type="error" variant="tonal" prominent>{{ erro }}</v-alert>

      <div v-else-if="projeto">
        <div class="text-center mb-10">
          <p class="text-overline text-green-darken-3">Resultados da Avaliação</p>
          <h1 class="text-h3 font-weight-bold text-green-darken-4">{{ projeto.titulo }}</h1>
        </div>

        <v-card
          v-if="avaliacoes.length === 0"
          flat
          border
          class="text-center pa-10"
        >
          <v-icon size="64" class="mb-4 text-grey-lighten-1">mdi-chart-gantt</v-icon>
          <p class="text-h6 text-grey-darken-2">Nenhuma avaliação submetida</p>
          <p class="text-grey-darken-1 mt-2">
            Este projeto ainda não recebeu nenhuma avaliação oficial.
          </p>
        </v-card>

        <div v-else>
          <v-row class="mb-8">
            <v-col cols="12" md="4">
              <v-card class="text-center fill-height" variant="tonal" color="green">
                <v-card-text>
                  <div class="text-h2 font-weight-bold">{{ notaMediaGeral }}</div>
                  <div class="text-overline">Nota Média Geral</div>
                </v-card-text>
              </v-card>
            </v-col>
            <v-col cols="12" md="8">
              <v-card class="fill-height" variant="outlined">
                <v-card-title>Média por Critério</v-card-title>
                <v-list class="bg-transparent">
                  <v-list-item v-for="criterio in notasPorCriterio" :key="criterio.nome">
                    <v-list-item-title>{{ criterio.nome }}</v-list-item-title>
                    <template v-slot:append>
                      <span class="font-weight-bold mr-4">{{ criterio.media }}</span>
                      <v-progress-linear :model-value="criterio.media" color="green-darken-2" style="width: 150px;"></v-progress-linear>
                    </template>
                  </v-list-item>
                </v-list>
              </v-card>
            </v-col>
          </v-row>

          <h2 class="text-h5 font-weight-medium mb-4">Avaliações Individuais ({{ avaliacoes.length }}/3)</h2>
          <v-expansion-panels variant="accordion">
            <v-expansion-panel v-for="(avaliacao, index) in avaliacoes" :key="avaliacao.id_avaliacao">
              <v-expansion-panel-title class="py-3">
                <v-row align="center">
                  <v-col cols="6" sm="4" class="d-flex align-center">
                    <v-avatar color="grey-lighten-2" class="mr-3">
                      <v-icon>mdi-account-tie-outline</v-icon>
                    </v-avatar>
                    <div>
                      <div class="font-weight-bold">Avaliador {{ index + 1 }}</div>
                      <div class="text-caption text-medium-emphasis">Avaliação Oficial</div>
                    </div>
                  </v-col>
                  <v-col cols="6" sm="4" class="text-center">
                    <div class="text-caption text-medium-emphasis">Nota Geral</div>
                    <v-chip color="green-darken-3" size="large" class="font-weight-bold">
                      {{ avaliacao.nota_geral }}
                    </v-chip>
                  </v-col>
                </v-row>
              </v-expansion-panel-title>
              <v-expansion-panel-text class="bg-grey-lighten-5 pa-6">
                <div class="mb-6">
                  <h4 class="font-weight-medium mb-2">Observações Gerais</h4>
                  <p class="font-italic text-medium-emphasis">"{{ avaliacao.observacoes || 'Nenhuma observação geral fornecida.' }}"</p>
                </div>
                <v-divider></v-divider>
                <h4 class="font-weight-medium my-4">Respostas por Pergunta</h4>
                <v-list lines="two" class="bg-transparent">
                  <div v-for="resposta in avaliacao.respostas" :key="resposta.id_resposta">
                    <v-list-item>
                      <v-list-item-title class="font-weight-medium">{{ resposta.pergunta.texto_pergunta }}</v-list-item-title>
                      <v-list-item-subtitle class="font-italic text-medium-emphasis mt-1" v-if="resposta.comentario_pergunta">
                        Comentário: "{{ resposta.comentario_pergunta }}"
                      </v-list-item-subtitle>
                       <template v-slot:append>
                        <v-chip :color="resposta.valor_resposta > 70 ? 'green' : (resposta.valor_resposta > 40 ? 'orange' : 'red')">
                          Nota: {{ resposta.valor_resposta }}
                        </v-chip>
                      </template>
                    </v-list-item>
                    <v-divider></v-divider>
                  </div>
                </v-list>
              </v-expansion-panel-text>
            </v-expansion-panel>
          </v-expansion-panels>
        </div>
      </div>
    </v-container>
  </div>
</template>
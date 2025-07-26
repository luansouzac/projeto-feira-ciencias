<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import api from '../assets/plugins/axios.js'; // Ajuste o caminho se necessário
import { useNotificationStore } from '@/stores/notification'; // Ajuste o caminho se necessário

const router = useRouter();
const notificationStore = useNotificationStore();

// --- ESTADOS DA PÁGINA ---
const carregando = ref(true);
const avaliacoes = ref([]);
let userId = null;

// --- ESTADOS DO MODAL DE AVALIAÇÃO ---
const isModalAvaliacaoOpen = ref(false);
const isModalLoading = ref(false);
const projetoSendoAvaliado = ref(null);
const tipoAvaliacao = ref(''); // 'aprovado', 'ressalva', 'reprovado'
const justificativa = ref('');

// --- MAPA DE STATUS (para os chips) ---
const statusMap = {
  1: { text: 'Submetido', color: 'blue' },
  2: { text: 'Aprovado', color: 'green' },
  3: { text: 'Reprovado', color: 'red' },
  4: { text: 'Ressalvas', color: 'orange' },
  5: { text: 'Em Desenvolvimento', color: 'teal' },
  6: { text: 'Concluído', color: 'purple' },
};

// --- CONFIGURAÇÃO DINÂMICA DO MODAL ---
const modalConfig = computed(() => {
  switch (tipoAvaliacao.value) {
    case 'aprovado':
      return { title: 'Aprovar Projeto', color: 'green-darken-2' };
    case 'ressalva':
      return { title: 'Reprovar com Ressalvas', color: 'orange-darken-2' };
    case 'reprovado':
      return { title: 'Reprovar Projeto', color: 'red-darken-2' };
    default:
      return { title: 'Confirmar Ação', color: 'grey' };
  }
});

// --- OBTENÇÃO DE DADOS ---
const buscarAvaliacoes = async () => {
  carregando.value = true;
  try {
    const userDataString = sessionStorage.getItem('user_data');
    if (userDataString) {
      const userData = JSON.parse(userDataString);
      userId = userData.user.id_usuario;
    } else {
      router.push({ name: 'login' });
      return;
    }
    const response = await api.get(`/usuarios/${userId}/projetos/avaliacao`);
    avaliacoes.value = response.data.filter(p => p.id_situacao === 1);
  } catch (err) {
    console.error("Erro ao buscar as avaliações:", err);
    notificationStore.showNotification({ message: 'Falha ao carregar projetos.', type: 'error' });
  } finally {
    carregando.value = false;
  }
};

onMounted(buscarAvaliacoes);

// --- FUNÇÕES DE AÇÃO ---
function goToProjectDetails(id){
  router.push(`/projetos/${id}`)
}

const openModal = (projeto, tipo) => {
  projetoSendoAvaliado.value = projeto;
  tipoAvaliacao.value = tipo;
  justificativa.value = '';
  isModalAvaliacaoOpen.value = true;
};

const closeModal = () => {
  isModalAvaliacaoOpen.value = false;
  isModalLoading.value = false;
  // Delay para animação de fechamento
  setTimeout(() => {
    projetoSendoAvaliado.value = null;
    tipoAvaliacao.value = '';
  }, 300);
};

const confirmarAvaliacao = async () => {
  if (tipoAvaliacao.value === 'ressalva' && !justificativa.value.trim()) {
    notificationStore.showNotification({ message: 'A justificativa é obrigatória para esta ação.', type: 'warning' });
    return;
  }
  
  isModalLoading.value = true;
  
  // Mapeia o tipo de avaliação para o ID da situação no backend
  const situacaoMap = {
    aprovado: 2,
    reprovado: 3,
    ressalva: 4
  };

  const payload = {
    id_situacao: situacaoMap[tipoAvaliacao.value],
    //justificativa: tipoAvaliacao.value === 'ressalva' ? justificativa.value : null,
  };

  try {

    await api.patch(`/projetos/${projetoSendoAvaliado.value.id_projeto}/situacao`, payload);
    avaliacoes.value = avaliacoes.value.filter(p => p.id_projeto !== projetoSendoAvaliado.value.id_projeto);
    isModalLoading.value = false;
    notificationStore.showNotification({ message: 'Projeto avaliado com sucesso!', type: 'success' });
    closeModal();

  } catch (err) {
    console.error("Erro ao confirmar avaliação:", err);
    notificationStore.showNotification({ message: 'Ocorreu um erro ao salvar a avaliação.', type: 'error' });
    isModalLoading.value = false;
  }
};
</script>

<template>
  <v-container fluid>

    <v-row class="mb-8">
      <v-col cols="12" sm="6" md="4">
        <v-card color="green-darken-4" dark class="d-flex flex-column" height="100%">
          <v-card-text>
            <div class="d-flex align-center">
              <v-icon size="48" class="mr-4">mdi-clipboard-list-outline</v-icon>
              <div>
                <div class="text-h4 font-weight-bold">{{ avaliacoes.length }}</div>
                <div class="text-subtitle-1">Avaliações Pendentes</div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      </v-row>

    <v-divider class="my-6"></v-divider>

    <v-row align="center" class="mb-4">
      <v-col cols="12">
        <h2 class="text-h5 font-weight-bold text-grey-darken-4">Projetos para Avaliação</h2>
        <p class="text-subtitle-2 text-grey-darken-1">Analise e forneça seu parecer sobre as propostas de projeto.</p>
      </v-col>
    </v-row>
    
    <v-row v-if="carregando">
      <v-col v-for="n in 3" :key="n" cols="12" sm="6" lg="4">
        <v-skeleton-loader type="card"></v-skeleton-loader>
      </v-col>
    </v-row>

    <v-row v-else-if="avaliacoes.length === 0">
      <v-col cols="12">
        <v-card flat border class="text-center pa-8">
          <v-icon size="60" class="mb-4 text-grey-lighten-1">mdi-check-all</v-icon>
          <p class="text-h6 text-grey-darken-2">Tudo certo por aqui!</p>
          <p class="text-grey-darken-1">Nenhuma avaliação pendente no momento.</p>
        </v-card>
      </v-col>
    </v-row>

    <v-row v-else>
      <v-col v-for="projeto in avaliacoes" :key="projeto.id_projeto" cols="12" sm="6" lg="4">
        <v-card class="d-flex flex-column" height="100%" hover variant="outlined">
          <v-card-item class="pb-0">
            <div class="d-flex justify-space-between align-start mb-2">
              <v-card-title class="text-wrap me-2">{{ projeto.titulo }}</v-card-title>
              <v-chip :color="statusMap[projeto.id_situacao]?.color || 'grey'" size="small" label>{{ statusMap[projeto.id_situacao]?.text || 'Pendente' }}</v-chip>
            </div>
          </v-card-item>
          <v-card-text class="py-2">
            <p class="text-body-2 text-grey-darken-2 text-truncate-3-lines">{{ projeto.problema }}</p>
          </v-card-text>
          <v-spacer></v-spacer>
          <v-divider></v-divider>
          <v-card-actions>
            <v-btn color="grey-darken-1" variant="text" @click="goToProjectDetails(projeto.id_projeto)">Ver Detalhes</v-btn>
            <v-spacer></v-spacer>
            
            <v-menu offset-y>
              <template v-slot:activator="{ props }">
                <v-btn color="blue-darken-2" variant="tonal" v-bind="props">
                  Avaliar
                  <v-icon right dark>mdi-chevron-down</v-icon>
                </v-btn>
              </template>
              <v-list density="compact">
                <v-list-item @click="openModal(projeto, 'aprovado')">
                  <template v-slot:prepend>
                    <v-icon color="green">mdi-check-circle-outline</v-icon>
                  </template>
                  <v-list-item-title>Aprovar</v-list-item-title>
                </v-list-item>
                <v-list-item @click="openModal(projeto, 'ressalva')">
                   <template v-slot:prepend>
                    <v-icon color="orange">mdi-alert-circle-outline</v-icon>
                  </template>
                  <v-list-item-title>Reprovar com Ressalvas</v-list-item-title>
                </v-list-item>
                <v-list-item @click="openModal(projeto, 'reprovado')">
                   <template v-slot:prepend>
                    <v-icon color="red">mdi-close-circle-outline</v-icon>
                  </template>
                  <v-list-item-title>Reprovar</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>

          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>

    <v-dialog v-model="isModalAvaliacaoOpen" persistent max-width="500px">
      <v-card>
        <v-card-title :class="['text-h5', 'white--text', modalConfig.color]">
          {{ modalConfig.title }}
        </v-card-title>
        <v-card-text class="pt-4">
          Você está prestes a avaliar o projeto <strong>"{{ projetoSendoAvaliado?.titulo }}"</strong>.
          <v-textarea
            v-if="tipoAvaliacao === 'ressalva'"
            v-model="justificativa"
            label="Justificativa / Ressalvas"
            placeholder="Descreva os pontos que precisam ser ajustados pelo aluno."
            rows="4"
            class="mt-4"
            variant="outlined"
            required
          ></v-textarea>
          <p v-if="tipoAvaliacao !== 'ressalva'" class="mt-4">Deseja confirmar esta ação?</p>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey-darken-1" text @click="closeModal">Cancelar</v-btn>
          <v-btn :color="modalConfig.color" variant="tonal" @click="confirmarAvaliacao" :loading="isModalLoading">
            Confirmar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

  </v-container>
</template>

<style scoped>
.text-wrap {
  white-space: normal !important;
  word-break: break-word;
}

.text-truncate-3-lines {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Garante que o título do modal tenha cor de texto branca */
.v-card-title.white--text {
  color: white !important;
}
</style>
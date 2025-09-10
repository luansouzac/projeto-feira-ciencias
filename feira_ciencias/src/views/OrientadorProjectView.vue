<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

// --- DADOS MOCADOS ---
const projeto = ref({
  id_projeto: 101,
  titulo: 'Sistema de Irrigação Automatizado com IoT',
  problema: 'Desenvolver um sistema de baixo custo que utiliza sensores de umidade e dados climáticos para otimizar o uso da água na agricultura familiar, reduzindo desperdício e aumentando a produtividade.',
  aluno_responsavel: { nome: 'Ana Clara Souza' },
  evento: { nome: 'Feira de Ciências e Tecnologia 2025' },
  id_situacao: 5, // Em Desenvolvimento
});

const tarefas = ref([
  { id_tarefa: 1, descricao: 'Pesquisa de sensores de umidade', detalhe: 'Analisar modelos, custos e precisão.', id_situacao: 3, feedbacks: [{ id: 1, autor: 'Prof. Carlos Andrade', texto: 'Ótima pesquisa! Os sensores DHT22 parecem ser a melhor opção de custo-benefício. Prossiga com a compra.' }] },
  { id_tarefa: 2, descricao: 'Desenvolvimento do hardware (Protótipo)', detalhe: 'Montar o circuito com o microcontrolador ESP32 e os sensores.', id_situacao: 2, feedbacks: [] },
  { id_tarefa: 3, descricao: 'Programação do firmware do microcontrolador', detalhe: 'Código para ler os sensores e acionar a válvula de água.', id_situacao: 1, feedbacks: [] },
  { id_tarefa: 4, descricao: 'Criação do banco de dados na nuvem', detalhe: 'Modelar e implementar o banco para armazenar os dados dos sensores.', id_situacao: 1, feedbacks: [] },
  { id_tarefa: 5, descricao: 'Testes de campo e calibração', detalhe: 'Instalar o protótipo em um canteiro e ajustar os parâmetros.', id_situacao: 1, feedbacks: [] },
  { id_tarefa: 6, descricao: 'Elaboração do relatório final', detalhe: 'Documentar todo o processo de desenvolvimento e resultados.', id_situacao: 3, feedbacks: [{ id: 2, autor: 'Prof. Carlos Andrade', texto: 'Relatório bem estruturado, mas lembre-se de adicionar o capítulo de análise de custos.' }] },
]);

// --- ESTADOS DO COMPONENTE ---
const activeTab = ref(null);
const isFeedbackModalOpen = ref(false);
const isModalLoading = ref(false);
const tarefaSelecionada = ref(null);
const novoFeedback = ref('');

// --- CONFIGURAÇÕES E COMPUTEDS ---
const statusMap = {
  5: { text: 'Em Desenvolvimento', color: 'blue-darken-2', icon: 'mdi-progress-wrench' },
  6: { text: 'Concluído', color: 'purple-darken-2', icon: 'mdi-trophy' },
};

const kanbanColumns = [
  { title: 'A Fazer', status: 1, color: 'grey' },
  { title: 'Em Andamento', status: 2, color: 'blue' },
  { title: 'Concluído', status: 3, color: 'green' },
];

const projetoStatus = computed(() => statusMap[projeto.value?.id_situacao] || {});
const filterTasksByStatus = (status) => tarefas.value.filter(task => task.id_situacao === status);

// --- FUNÇÕES DE AÇÃO ---
const openFeedbackModal = (tarefa) => {
  tarefaSelecionada.value = tarefa;
  novoFeedback.value = '';
  isFeedbackModalOpen.value = true;
};

const closeFeedbackModal = () => {
  isFeedbackModalOpen.value = false;
  setTimeout(() => {
    tarefaSelecionada.value = null;
  }, 300);
};

const submeterFeedback = () => {
  if (!novoFeedback.value.trim()) {
    // Aqui você pode adicionar uma notificação da sua store, se desejar
    console.warn("O feedback não pode estar vazio.");
    return;
  }
  isModalLoading.value = true;
  // Simula uma chamada de API
  setTimeout(() => {
    const feedbackParaAdicionar = {
      id: Date.now(), // ID único provisório
      autor: 'Prof. Carlos Andrade', // Nome do orientador logado
      texto: novoFeedback.value,
    };
    tarefaSelecionada.value.feedbacks.push(feedbackParaAdicionar);
    isModalLoading.value = false;
    closeFeedbackModal();
  }, 1000);
};

const formatDate = (date) => new Date(date).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' });
</script>

<template>
  <v-container fluid>
    <v-btn variant="text" prepend-icon="mdi-arrow-left" @click="router.go(-1)" class="mb-6">
      Voltar
    </v-btn>

    <!-- CABEÇALHO DO PROJETO -->
    <v-card theme="dark" class="mb-8 bg-green-darken-4">
      <v-card-item class="pa-4 pa-sm-6">
        <div class="d-flex flex-wrap justify-space-between align-center">
          <div>
            <p class="text-overline">Gerenciamento do Projeto</p>
            <h1 class="text-h4 font-weight-bold">{{ projeto.titulo }}</h1>
            <div class="d-flex align-center mt-2 opacity-75">
              <v-icon size="small" start icon="mdi-account-school"></v-icon>
              <span class="text-subtitle-1">Responsável: {{ projeto.aluno_responsavel.nome }}</span>
            </div>
          </div>
          <v-chip :color="projetoStatus.color" :prepend-icon="projetoStatus.icon" variant="tonal" label>
            {{ projetoStatus.text }}
          </v-chip>
        </div>
      </v-card-item>
    </v-card>

    <!-- ABAS DE NAVEGAÇÃO -->
    <v-card>
      <v-tabs v-model="activeTab" bg-color="green-darken-3" color="white" grow>
        <v-tab value="kanban">
          <v-icon start>mdi-view-dashboard-outline</v-icon>
          Quadro de Tarefas
        </v-tab>
        <v-tab value="detalhes">
          <v-icon start>mdi-text-box-search-outline</v-icon>
          Detalhes da Proposta
        </v-tab>
      </v-tabs>

      <v-window v-model="activeTab">
        <!-- ABA 1: QUADRO KANBAN -->
        <v-window-item value="kanban">
          <v-card-text class="bg-grey-lighten-4 pa-4 pa-md-6">
            <v-row>
              <v-col v-for="column in kanbanColumns" :key="column.status" cols="12" md="4">
                <div class="pa-4 rounded-lg fill-height" :class="`bg-${column.color}-lighten-5`">
                  <div class="d-flex align-center mb-4">
                    <v-icon :color="column.color" class="mr-2">mdi-circle-medium</v-icon>
                    <span class="font-weight-bold text-grey-darken-3">{{ column.title }}</span>
                    <v-chip size="small" :color="column.color" class="ml-2">{{ filterTasksByStatus(column.status).length }}</v-chip>
                  </div>
                  
                  <v-card v-for="tarefa in filterTasksByStatus(column.status)" :key="tarefa.id_tarefa" class="mb-3 task-card" theme="light" variant="flat">
                    <v-card-text class="pb-0">
                      <p class="font-weight-medium text-grey-darken-4">{{ tarefa.descricao }}</p>
                      <p v-if="tarefa.detalhe" class="text-caption font-weight-regular text-grey-darken-1 mt-1">{{ tarefa.detalhe }}</p>
                    </v-card-text>
                    <v-card-actions>
                      <v-chip v-if="tarefa.feedbacks.length > 0" size="small" prepend-icon="mdi-comment-text-multiple-outline" color="blue-grey" variant="tonal">
                        {{ tarefa.feedbacks.length }} Feedback(s)
                      </v-chip>
                      <v-spacer></v-spacer>
                      <v-btn color="green-darken-2" variant="text" @click="openFeedbackModal(tarefa)">
                        Analisar
                      </v-btn>
                    </v-card-actions>
                  </v-card>
                </div>
              </v-col>
            </v-row>
          </v-card-text>
        </v-window-item>

        <!-- ABA 2: DETALHES -->
        <v-window-item value="detalhes">
          <v-card-text class="pa-4 pa-md-6">
            <v-list lines="two" bg-color="transparent">
              <v-list-item prepend-icon="mdi-lightbulb-on-outline" title="Problema a ser Resolvido" :subtitle="projeto.problema"></v-list-item>
              <v-list-item-subtitle class="pl-14 text-wrap">{{ projeto.problema }}</v-list-item-subtitle>
            </v-list>
          </v-card-text>
        </v-window-item>
      </v-window>
    </v-card>

    <!-- MODAL DE FEEDBACK -->
    <v-dialog v-model="isFeedbackModalOpen" persistent max-width="700px">
      <v-card>
        <v-card-title class="text-h5 bg-green-darken-3 text-white">
          <v-icon start>mdi-comment-edit-outline</v-icon>
          Analisar Tarefa
        </v-card-title>
        <v-card-text class="pt-6">
          <p class="font-weight-bold text-h6 text-grey-darken-4">{{ tarefaSelecionada?.descricao }}</p>
          <p class="text-body-2 text-grey-darken-2 mt-1">{{ tarefaSelecionada?.detalhe }}</p>
          
          <v-divider class="my-6"></v-divider>

          <h3 class="text-h6 font-weight-medium mb-4 text-grey-darken-3">Histórico de Feedbacks</h3>
          <div v-if="tarefaSelecionada?.feedbacks.length === 0" class="text-center text-grey-darken-1 pa-4">
            <p>Nenhum feedback registrado para esta tarefa ainda.</p>
          </div>
          <v-timeline v-else side="end" align="start" density="compact" truncate-line="both">
            <v-timeline-item v-for="feedback in tarefaSelecionada?.feedbacks" :key="feedback.id" dot-color="green-darken-1" size="small">
              <div class="feedback-item">
                <p class="font-italic">"{{ feedback.texto }}"</p>
                <div class="text-caption text-grey-darken-1 mt-1">
                  - {{ feedback.autor }} em {{ formatDate(feedback.id) }}
                </div>
              </div>
            </v-timeline-item>
          </v-timeline>

          <v-divider class="my-6"></v-divider>
          
          <h3 class="text-h6 font-weight-medium mb-4 text-grey-darken-3">Adicionar Novo Feedback</h3>
          <v-textarea
            v-model="novoFeedback"
            label="Seu parecer sobre a tarefa"
            placeholder="Descreva os pontos positivos, áreas para melhoria, ou próximos passos..."
            rows="4"
            variant="outlined"
            autofocus
          ></v-textarea>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn color="grey-darken-1" variant="text" @click="closeFeedbackModal" :disabled="isModalLoading">Cancelar</v-btn>
          <v-btn :color="`green-darken-2`" variant="flat" @click="submeterFeedback" :loading="isModalLoading">
            Enviar Feedback
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<style scoped>
.fill-height {
  min-height: 400px;
}
.task-card {
  transition: box-shadow 0.2s ease-in-out, transform 0.2s ease-in-out;
}
.task-card:hover {
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}
.text-wrap {
  white-space: normal !important;
}
.feedback-item {
  border-left: 3px solid #E0E0E0; /* cinza claro */
  padding-left: 16px;
}
</style>
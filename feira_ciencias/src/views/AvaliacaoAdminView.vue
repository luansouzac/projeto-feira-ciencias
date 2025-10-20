<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useNotificationStore } from '@/stores/notification';
import api from '@/assets/plugins/axios.js';

// Importação dos componentes de modal que serão utilizados nesta página
import QuestionarioFormModal from '@/components/modals/QuestionarioFormModal.vue';
import PerguntaFormModal from '@/components/modals/PerguntaFormModal.vue';

// --- Stores e Estado Geral da Página ---
const notificationStore = useNotificationStore();
const loading = ref(true);
const erro = ref(null);
const activeTab = ref('atribuicoes');
const isSubmitting = ref(false); // Usado para os botões de loading

// --- Dados Carregados da API ---
const projetos = ref([]);
const avaliadoresDisponiveis = ref([]); // Todos os utilizadores que podem ser avaliadores
const questionarios = ref([]);
const eventos = ref([]);

// --- Estado da Aba "Atribuições" ---
const atribuicoes = ref([]); // Avaliadores já atribuídos ao projeto selecionado
const selectedProjectId = ref(null);
const selectedAvaliadorId = ref(null);

// --- Estado da Aba "Questionários" e seus Modais ---
const isQuestionarioModalOpen = ref(false);
const isPerguntaModalOpen = ref(false);
const questionarioParaEditar = ref(null);
const questionarioParaAdicionarPergunta = ref(null);
const perguntaParaEditar = ref(null);

// --- Busca de Dados Iniciais ---
onMounted(async () => {
  try {
    // Faz todas as chamadas à API em paralelo para otimizar o carregamento
    const [projetosResponse, avaliadoresResponse, questionariosResponse, eventosResponse] = await Promise.all([
      api.get('/projetos'), // Busca todos os projetos para o seletor
      api.get('/usuarios?id_tipo_usuario_in=3,4'), // Busca utilizadores do tipo 3 (Avaliador) e 4 (Orientador)
      api.get('/questionarios'), // Busca os questionários existentes
      api.get('/eventos') // Busca os eventos para o formulário de questionário
    ]);
    projetos.value = projetosResponse.data;
    avaliadoresDisponiveis.value = avaliadoresResponse.data;
    questionarios.value = questionariosResponse.data;
    eventos.value = eventosResponse.data;
  } catch (err) {
    erro.value = "Não foi possível carregar os dados necessários para a página.";
    console.error(err);
  } finally {
    loading.value = false;
  }
});

// --- Lógica da Aba "Atribuição de Avaliadores" ---

// Observa quando o utilizador seleciona um projeto na lista e busca os avaliadores já atribuídos
watch(selectedProjectId, async (newProjectId) => {
  if (!newProjectId) {
    atribuicoes.value = [];
    return;
  }
  try {
    const response = await api.get(`/projetos/${newProjectId}/avaliadores`);
    atribuicoes.value = response.data;
  } catch (err) {
    notificationStore.showError("Falha ao buscar os avaliadores deste projeto.");
  }
});

// Atribui um novo avaliador ao projeto selecionado
const atribuirAvaliador = async () => {
  if (!selectedProjectId.value || !selectedAvaliadorId.value) {
    notificationStore.showWarning("Por favor, selecione um projeto e um avaliador.");
    return;
  }
  isSubmitting.value = true;
  try {
    const payload = {
      id_projeto: selectedProjectId.value,
      id_avaliador: selectedAvaliadorId.value,
    };
    const response = await api.post('/avaliador_projeto', payload);
    
    // Atualiza a lista na interface com os dados completos do avaliador
    const avaliadorInfo = avaliadoresDisponiveis.value.find(a => a.id_usuario === response.data.id_avaliador);
    atribuicoes.value.push({ ...response.data, avaliador: avaliadorInfo });
    
    selectedAvaliadorId.value = null; // Limpa o seletor
    notificationStore.showSuccess("Avaliador atribuído com sucesso!");
  } catch (err) {
    notificationStore.showError(err.response?.data?.erro || "Não foi possível atribuir o avaliador.");
  } finally {
    isSubmitting.value = false;
  }
};

// Remove uma atribuição de avaliação
const removerAtribuicao = async (atribuicao) => {
  if (!confirm(`Tem a certeza de que deseja remover ${atribuicao.avaliador.nome} deste projeto?`)) return;

  try {
    await api.delete(`/avaliador_projeto/${atribuicao.id}`);
    atribuicoes.value = atribuicoes.value.filter(a => a.id !== atribuicao.id);
    notificationStore.showSuccess("Avaliador desassociado com sucesso.");
  } catch (err) {
    notificationStore.showError(err.response?.data?.erro || "Não foi possível remover a atribuição.");
  }
};

// Filtra a lista de avaliadores para não mostrar quem já foi atribuído ao projeto atual
const avaliadoresParaAtribuir = computed(() => {
  const idsAtribuidos = atribuicoes.value.map(a => a.id_avaliador);
  return avaliadoresDisponiveis.value.filter(a => !idsAtribuidos.includes(a.id_usuario));
});

const eventosDisponiveisParaQuestionario = computed(() => {
  const idsEventosUsados = new Set(questionarios.value.map(q => q.id_evento));
  
  return eventos.value.filter(evento => {
    if (!idsEventosUsados.has(evento.id_evento)) {
      return true;
    }
    if (questionarioParaEditar.value && questionarioParaEditar.value.id_evento === evento.id_evento) {
      return true;
    }
    return false;
  });
});


// --- Lógica da Aba "Gestão de Questionários" ---

const openCreateQuestionarioModal = () => {
  questionarioParaEditar.value = null;
  isQuestionarioModalOpen.value = true;
};
const openEditQuestionarioModal = (questionario) => {
  questionarioParaEditar.value = { ...questionario };
  isQuestionarioModalOpen.value = true;
};
const handleSaveQuestionario = async (formData) => {
  isSubmitting.value = true;
  try {
    if (formData.id_questionario) { 
      const response = await api.put(`/questionarios/${formData.id_questionario}`, formData);
      const index = questionarios.value.findIndex(q => q.id_questionario === formData.id_questionario);
      if (index !== -1) questionarios.value[index] = response.data;
      notificationStore.showSuccess("Questionário atualizado com sucesso!");
    } else { 
      const response = await api.post('/questionarios', formData);
      questionarios.value.unshift(response.data);
      notificationStore.showSuccess("Questionário criado com sucesso!");
    }
    isQuestionarioModalOpen.value = false;
  } catch (err) {
    notificationStore.showError("Falha ao salvar o questionário.");
  } finally {
    isSubmitting.value = false;
  }
};
const handleDeleteQuestionario = async (questionario) => {
    if (!confirm(`Tem a certeza de que deseja apagar o questionário "${questionario.titulo}"? Esta ação não pode ser desfeita.`)) return;
    try {
        await api.delete(`/questionarios/${questionario.id_questionario}`);
        questionarios.value = questionarios.value.filter(q => q.id_questionario !== questionario.id_questionario);
        notificationStore.showSuccess("Questionário apagado com sucesso.");
    } catch(err) {
        notificationStore.showError("Não foi possível apagar o questionário.");
    }
};
const openAddPerguntaModal = (questionario) => {
  questionarioParaAdicionarPergunta.value = questionario;
  isPerguntaModalOpen.value = true;
};

const handleDeletePergunta = async (pergunta, questionario) => {
    if (!confirm(`Tem a certeza de que deseja apagar a pergunta: "${pergunta.texto_pergunta}"?`)) return;
    try {
        await api.delete(`/perguntas_questionario/${pergunta.id_pergunta}`);
        const qIndex = questionarios.value.findIndex(q => q.id_questionario === questionario.id_questionario);
        if (qIndex !== -1) {
            const pIndex = questionarios.value[qIndex].perguntas.findIndex(p => p.id_pergunta === pergunta.id_pergunta);
            if (pIndex !== -1) {
                questionarios.value[qIndex].perguntas.splice(pIndex, 1);
            }
        }
        notificationStore.showSuccess("Pergunta apagada com sucesso.");
    } catch(err) {
        notificationStore.showError("Não foi possível apagar a pergunta.");
    }
};


const openEditPerguntaModal = (pergunta, questionario) => {
  questionarioParaAdicionarPergunta.value = questionario;
  perguntaParaEditar.value = { ...pergunta };
  isPerguntaModalOpen.value = true;
};

const handleSavePergunta = async (formData) => {
  isSubmitting.value = true;
  try {
    const questionario = questionarios.value.find(q => q.id_questionario === formData.id_questionario);
    if (!questionario) throw new Error("Questionário não encontrado");

    if (formData.id_pergunta) { // A CONDIÇÃO QUE ESTÁ A FALHAR
      const response = await api.put(`/perguntas_questionario/${formData.id_pergunta}`, formData);
      const pIndex = questionario.perguntas.findIndex(p => p.id_pergunta === formData.id_pergunta);
      if (pIndex !== -1) {
        questionario.perguntas[pIndex] = response.data;
      }
      notificationStore.showSuccess("Pergunta atualizada com sucesso!");
    } else {
      const response = await api.post('/perguntas_questionario', formData);
      if (!questionario.perguntas) questionario.perguntas = [];
      questionario.perguntas.push(response.data);
      notificationStore.showSuccess("Pergunta adicionada com sucesso!");
    }
    
    isPerguntaModalOpen.value = false;
  } catch(err) {
    notificationStore.showError(err.response?.data?.erro || "Falha ao salvar a pergunta.");
  } finally {
    isSubmitting.value = false;
  }
};
const getEventName = (eventId) => eventos.value.find(e => e.id_evento === eventId)?.nome || 'Evento desconhecido';
</script>

<template>
  <v-container>
    <div v-if="loading" class="text-center py-16">
      <v-progress-circular indeterminate color="green-darken-3" size="64" />
      <p class="mt-4 text-grey-darken-1">A carregar painel de gestão...</p>
    </div>
    <v-alert v-else-if="erro" type="error" variant="tonal" prominent>{{ erro }}</v-alert>

    <div v-else>
      <h1 class="text-h4 font-weight-bold mb-2">Painel de Gestão de Avaliações</h1>
      <p class="text-medium-emphasis mb-8">Atribua avaliadores aos projetos e gira os questionários para os eventos.</p>

      <v-card>
        <v-tabs v-model="activeTab" bg-color="green-darken-4" color="white" grow>
          <v-tab value="atribuicoes">Atribuição de Avaliadores</v-tab>
          <v-tab value="questionarios">Gestão de Questionários</v-tab>
        </v-tabs>

        <v-window v-model="activeTab">
          <!-- Aba de Atribuições -->
          <v-window-item value="atribuicoes">
            <v-card-text class="pa-6">
              <v-select
                v-model="selectedProjectId"
                :items="projetos"
                item-title="titulo"
                item-value="id_projeto"
                label="Selecione um Projeto para Gerir"
                variant="outlined"
                class="mb-6"
                clearable
              ></v-select>

              <div v-if="selectedProjectId">
                <h2 class="text-h6 font-weight-medium mb-4">Avaliadores Atribuídos ({{ atribuicoes.length }}/3)</h2>
                <v-list v-if="atribuicoes.length > 0" lines="one" border rounded class="mb-8">
                  <v-list-item v-for="atribuicao in atribuicoes" :key="atribuicao.id" :title="atribuicao.avaliador.nome">
                    <template v-slot:prepend>
                      <v-avatar color="grey-lighten-2">
                        <v-icon>mdi-account-tie-outline</v-icon>
                      </v-avatar>
                    </template>
                    <template v-slot:append>
                      <v-btn icon="mdi-close" variant="text" color="grey" @click="removerAtribuicao(atribuicao)"></v-btn>
                    </template>
                  </v-list-item>
                </v-list>
                <p v-else class="text-center text-grey py-4">Nenhum avaliador atribuído a este projeto ainda.</p>

                <!-- Formulário para adicionar novo avaliador -->
                <div v-if="atribuicoes.length < 3">
                  <h2 class="text-h6 font-weight-medium mb-4">Adicionar Novo Avaliador</h2>
                  <v-row align="center">
                    <v-col cols="12" md="8">
                      <v-select
                        v-model="selectedAvaliadorId"
                        :items="avaliadoresParaAtribuir"
                        item-title="nome"
                        item-value="id_usuario"
                        label="Selecione um Avaliador"
                        variant="outlined"
                        hide-details
                      ></v-select>
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-btn 
                        color="green-darken-3" 
                        @click="atribuirAvaliador"
                        :loading="isSubmitting"
                        block 
                        size="large"
                      >
                        Atribuir
                      </v-btn>
                    </v-col>
                  </v-row>
                </div>
                 <v-alert v-else type="success" variant="tonal" class="mt-6">
                    Este projeto já atingiu o número máximo de 3 avaliadores.
                </v-alert>

              </div>
            </v-card-text>
          </v-window-item>

          <!-- Aba de Questionários -->
          <v-window-item value="questionarios">
            <v-toolbar flat>
              <v-toolbar-title class="font-weight-medium">Questionários</v-toolbar-title>
              <v-spacer></v-spacer>
              <v-btn color="green-darken-3" variant="flat" @click="openCreateQuestionarioModal" prepend-icon="mdi-plus">
                Novo Questionário
              </v-btn>
            </v-toolbar>
            <v-divider></v-divider>

            <v-card-text>
              <v-expansion-panels>
                <v-expansion-panel v-for="q in questionarios" :key="q.id_questionario">
                  <v-expansion-panel-title>
                    <div>
                      <div class="font-weight-bold">{{ q.titulo }}</div>
                      <div class="text-caption text-medium-emphasis">Evento: {{ getEventName(q.id_evento) }}</div>
                    </div>
                     <v-spacer></v-spacer>
                     <v-btn icon="mdi-pencil" variant="text" size="small" @click.stop="openEditQuestionarioModal(q)" class="mr-2"></v-btn>
                     <v-btn icon="mdi-delete" variant="text" size="small" color="red-lighten-1" @click.stop="handleDeleteQuestionario(q)"></v-btn>
                  </v-expansion-panel-title>
                  <v-expansion-panel-text class="bg-grey-lighten-5">
                    <v-list-subheader>Perguntas do Questionário</v-list-subheader>
                     <v-list v-if="q.perguntas && q.perguntas.length > 0" lines="two" class="bg-transparent">
                        <div v-for="(pergunta, index) in q.perguntas" :key="pergunta.id_pergunta">
                            <v-list-item :title="pergunta.texto_pergunta" :subtitle="`Critério: ${pergunta.criterio}`">
                              <template v-slot:append>
                                <v-btn icon="mdi-pencil" variant="text" size="small" @click.stop="openEditPerguntaModal(pergunta, q)"></v-btn>
                                <v-btn icon="mdi-delete" variant="text" size="small" color="red-lighten-1" @click.stop="handleDeletePergunta(pergunta, q)"></v-btn>
                              </template>
                            </v-list-item>
                            <v-divider v-if="index < q.perguntas.length - 1"></v-divider>
                        </div>
                     </v-list>
                     <p v-else class="text-center text-grey py-4">Nenhuma pergunta adicionada ainda.</p>

                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn color="green-darken-2" variant="text" @click="openAddPerguntaModal(q)" prepend-icon="mdi-plus">
                            Adicionar Pergunta
                        </v-btn>
                    </v-card-actions>
                  </v-expansion-panel-text>
                </v-expansion-panel>
              </v-expansion-panels>
            </v-card-text>
          </v-window-item>
        </v-window>
      </v-card>
    </div>

    <!-- Modais -->
    <QuestionarioFormModal 
      v-model="isQuestionarioModalOpen"
      :questionario-to-edit="questionarioParaEditar"
      :eventos="eventos"
      :is-loading="isSubmitting"
      @save="handleSaveQuestionario"
    />
    <PerguntaFormModal
      v-model="isPerguntaModalOpen"
      :questionario-id="questionarioParaAdicionarPergunta?.id_questionario"
      :is-loading="isSubmitting"
      :pergunta-to-edit="perguntaParaEditar"
      @save="handleSavePergunta"
    />
  </v-container>
</template>


<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useNotificationStore } from '@/stores/notification';
import api from '@/assets/plugins/axios.js';

// Importação dos componentes de modal
import QuestionarioFormModal from '@/components/modals/QuestionarioFormModal.vue';
import PerguntaFormModal from '@/components/modals/PerguntaFormModal.vue';

// --- Stores e Estado Geral ---
const notificationStore = useNotificationStore();
const loading = ref(true);
const erro = ref(null);
const activeTab = ref('atribuicoes');
const isSubmitting = ref(false); 
const loadingAvaliadores = ref(false); // Loading específico para o campo de pesquisa

// --- Dados da API ---
const projetos = ref([]);
const avaliadoresDisponiveis = ref([]); // Lista dinâmica baseada no evento do projeto
const questionarios = ref([]);
const eventos = ref([]);

// --- Estado da Aba "Atribuições" ---
const atribuicoes = ref([]); 
const selectedProjectId = ref(null);
const selectedAvaliadorId = ref(null);

// --- Estado da Aba "Questionários" e Modais ---
const isQuestionarioModalOpen = ref(false);
const isPerguntaModalOpen = ref(false);
const questionarioParaEditar = ref(null);
const questionarioParaAdicionarPergunta = ref(null);
const perguntaParaEditar = ref(null);

// --- Busca de Dados Iniciais ---
onMounted(async () => {
  try {
    // Carregamos apenas o básico. Os avaliadores agora são carregados sob demanda.
    const [projetosResponse, questionariosResponse, eventosResponse] = await Promise.all([
      api.get('/projetos'), 
      api.get('/questionarios'), 
      api.get('/eventos') 
    ]);
    projetos.value = projetosResponse.data;
    questionarios.value = questionariosResponse.data;
    eventos.value = eventosResponse.data;
  } catch (err) {
    erro.value = "Não foi possível carregar os dados iniciais do painel.";
    console.error(err);
  } finally {
    loading.value = false;
  }
});

// --- Lógica da Aba "Atribuição de Avaliadores" ---

// Observa o projeto selecionado para buscar os dados corretos
watch(selectedProjectId, async (newProjectId) => {
  // Limpa estados anteriores
  atribuicoes.value = [];
  avaliadoresDisponiveis.value = [];
  selectedAvaliadorId.value = null;

  if (!newProjectId) return;

  loadingAvaliadores.value = true; 

  try {
    const projetoSelecionado = projetos.value.find(p => p.id_projeto === newProjectId);
    
    const promises = [
       api.get(`/projetos/${newProjectId}/avaliadores`) // Quem já está atribuído
    ];

    if (projetoSelecionado && projetoSelecionado.id_evento) {
        promises.push(api.get(`/eventos/${projetoSelecionado.id_evento}/avaliadores`));
    }

    const [atribuicoesResponse, avaliadoresResponse] = await Promise.all(promises);

    atribuicoes.value = atribuicoesResponse.data;

    if (avaliadoresResponse) {
        avaliadoresDisponiveis.value = avaliadoresResponse.data.map(item => item.avaliador);
    } else {
        notificationStore.showWarning("Este projeto não tem um evento associado para buscar avaliadores.");
    }

  } catch (err) {
    notificationStore.showError("Falha ao carregar dados do projeto ou avaliadores.");
    console.error(err);
  } finally {
    loadingAvaliadores.value = false;
  }
});

// Atribui novo avaliador
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
    
    // Atualiza visualmente a lista sem precisar recarregar tudo
    const avaliadorInfo = avaliadoresDisponiveis.value.find(a => a.id_usuario === response.data.id_avaliador);
    atribuicoes.value.push({ ...response.data, avaliador: avaliadorInfo });
    
    selectedAvaliadorId.value = null; 
    notificationStore.showSuccess("Avaliador atribuído com sucesso!");
  } catch (err) {
    notificationStore.showError(err.response?.data?.erro || "Não foi possível atribuir o avaliador.");
  } finally {
    isSubmitting.value = false;
  }
};

// Remove atribuição
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

// Computada para filtrar quem já está no projeto
const avaliadoresParaAtribuir = computed(() => {
  const idsAtribuidos = atribuicoes.value.map(a => a.id_avaliador);
  return avaliadoresDisponiveis.value.filter(a => !idsAtribuidos.includes(a.id_usuario));
});

// --- Lógica da Aba "Gestão de Questionários" ---

const getEventName = (eventId) => eventos.value.find(e => e.id_evento === eventId)?.nome || 'Evento desconhecido';

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
    if (!confirm(`Tem a certeza de que deseja apagar o questionário "${questionario.titulo}"?`)) return;
    try {
        await api.delete(`/questionarios/${questionario.id_questionario}`);
        questionarios.value = questionarios.value.filter(q => q.id_questionario !== questionario.id_questionario);
        notificationStore.showSuccess("Questionário apagado.");
    } catch(err) { notificationStore.showError("Erro ao apagar questionário."); }
};
const openAddPerguntaModal = (questionario) => {
  questionarioParaAdicionarPergunta.value = questionario;
  isPerguntaModalOpen.value = true;
};
const openEditPerguntaModal = (pergunta, questionario) => {
  questionarioParaAdicionarPergunta.value = questionario;
  perguntaParaEditar.value = { ...pergunta };
  isPerguntaModalOpen.value = true;
};
const handleDeletePergunta = async (pergunta, questionario) => {
    if (!confirm("Apagar pergunta?")) return;
    try {
        await api.delete(`/perguntas_questionario/${pergunta.id_pergunta}`);
        const qIndex = questionarios.value.findIndex(q => q.id_questionario === questionario.id_questionario);
        if (qIndex !== -1) {
             const pIndex = questionarios.value[qIndex].perguntas.findIndex(p => p.id_pergunta === pergunta.id_pergunta);
             if (pIndex !== -1) questionarios.value[qIndex].perguntas.splice(pIndex, 1);
        }
        notificationStore.showSuccess("Pergunta apagada.");
    } catch(err) { notificationStore.showError("Erro ao apagar pergunta."); }
};
const handleSavePergunta = async (formData) => {
  isSubmitting.value = true;
  try {
    const questionario = questionarios.value.find(q => q.id_questionario === formData.id_questionario);
    if (!questionario) throw new Error("Questionário não encontrado");
    if (formData.id_pergunta) {
      const response = await api.put(`/perguntas_questionario/${formData.id_pergunta}`, formData);
      const pIndex = questionario.perguntas.findIndex(p => p.id_pergunta === formData.id_pergunta);
      if (pIndex !== -1) questionario.perguntas[pIndex] = response.data;
      notificationStore.showSuccess("Pergunta atualizada!");
    } else {
      const response = await api.post('/perguntas_questionario', formData);
      if (!questionario.perguntas) questionario.perguntas = [];
      questionario.perguntas.push(response.data);
      notificationStore.showSuccess("Pergunta adicionada!");
    }
    isPerguntaModalOpen.value = false;
  } catch(err) {
    notificationStore.showError(err.response?.data?.erro || "Falha ao salvar pergunta.");
  } finally { isSubmitting.value = false; }
};
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
      <p class="text-medium-emphasis mb-8">Atribua avaliadores aos projetos e gira os questionários.</p>

      <v-card>
        <v-tabs v-model="activeTab" bg-color="green-darken-4" color="white" grow>
          <v-tab value="atribuicoes">Atribuição de Avaliadores</v-tab>
          <v-tab value="questionarios">Gestão de Questionários</v-tab>
        </v-tabs>

        <v-window v-model="activeTab">
          
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
                      <v-avatar color="grey-lighten-2"><v-icon>mdi-account-tie-outline</v-icon></v-avatar>
                    </template>
                    <template v-slot:append>
                      <v-btn icon="mdi-close" variant="text" color="grey" @click="removerAtribuicao(atribuicao)"></v-btn>
                    </template>
                  </v-list-item>
                </v-list>
                <p v-else class="text-center text-grey py-4">Nenhum avaliador atribuído a este projeto ainda.</p>

                <div v-if="atribuicoes.length < 3">
                  <h2 class="text-h6 font-weight-medium mb-4">Adicionar Novo Avaliador</h2>
                  <v-row align="center">
                    <v-col cols="12" md="8">
                      
                      <v-autocomplete
                        v-model="selectedAvaliadorId"
                        :items="avaliadoresParaAtribuir"
                        item-title="nome"
                        item-value="id_usuario"
                        label="Pesquisar Avaliador do Evento"
                        placeholder="Digite o nome..."
                        variant="outlined"
                        hide-details
                        clearable
                        :loading="loadingAvaliadores"
                        :disabled="loadingAvaliadores || !selectedProjectId"
                        no-data-text="Nenhum avaliador disponível para este evento"
                      ></v-autocomplete>

                    </v-col>
                    <v-col cols="12" md="4">
                      <v-btn 
                        color="green-darken-3" 
                        @click="atribuirAvaliador"
                        :loading="isSubmitting"
                        :disabled="!selectedAvaliadorId"
                        block 
                        size="large"
                      >
                        Atribuir
                      </v-btn>
                    </v-col>
                  </v-row>
                </div>
                 <v-alert v-else type="success" variant="tonal" class="mt-6">
                    Este projeto já atingiu o limite de 3 avaliadores.
                </v-alert>

              </div>
            </v-card-text>
          </v-window-item>

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
                    <v-list-subheader>Perguntas</v-list-subheader>
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
                     <p v-else class="text-center text-grey py-4">Sem perguntas.</p>
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
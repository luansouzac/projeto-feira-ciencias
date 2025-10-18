<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/assets/plugins/axios.js';
import { useAuthStore } from '@/stores/authStore';
import { useNotificationStore } from '@/stores/notification';

// --- Instâncias e Stores ---
const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const notificationStore = useNotificationStore();

// --- Estado da Página ---
const projeto = ref(null);
const questionario = ref(null);
const atribuicao = ref(null); // ✅ NOVO: Armazena a "tarefa de avaliação"
const loading = ref(true);
const erro = ref(null);
const isSubmitting = ref(false);

// --- Estado do Formulário ---
const respostas = ref({}); // Ex: { 1: 50, 2: 100, ... }
const observacoes = ref('');
const notaGeral = ref(75);
const isConfirmDialogOpen = ref(false);

// --- Busca de Dados ---
onMounted(async () => {
  const projetoId = route.params.id;
  try {
    // Busca os dados do projeto, o questionário e a atribuição do utilizador em paralelo
    const [projetoResponse, questionarioResponse, atribuicaoResponse] = await Promise.all([
      api.get(`/projetos/${projetoId}`),
      api.get('/questionarios?ativo=true'), // Busca o primeiro questionário ativo
      api.get(`/projetos/${projetoId}/minha-atribuicao`) // ✅ BUSCA A "TAREFA DE AVALIAÇÃO"
    ]);

    projeto.value = projetoResponse.data;
    atribuicao.value = atribuicaoResponse.data; // Armazena a resposta da nova rota
    
    if (questionarioResponse.data && questionarioResponse.data.length > 0) {
      questionario.value = questionarioResponse.data[0];
    } else {
      throw new Error("Nenhum questionário de avaliação ativo foi encontrado.");
    }

    // Validação: se a avaliação já foi concluída, bloqueia a página
    if (atribuicao.value?.status === 'concluida') {
        throw new Error("Você já submeteu a avaliação para este projeto.");
    }

  } catch (error) {
    console.error("Erro ao carregar dados da página:", error);
    erro.value = error.response?.data?.erro || error.message || "Não foi possível carregar os dados para a avaliação.";
  } finally {
    loading.value = false;
  }
});

// --- Propriedades Computadas ---
const isAluno = computed(() => authStore.user?.id_tipo_usuario === 2);

const perguntasAgrupadas = computed(() => {
  if (!questionario.value?.perguntas) return {};
  return questionario.value.perguntas.reduce((acc, pergunta) => {
    if (!acc[pergunta.criterio]) {
      acc[pergunta.criterio] = [];
    }
    acc[pergunta.criterio].push(pergunta);
    return acc;
  }, {});
});

const isFormValid = computed(() => {
    if(isAluno.value) return true;
    const totalPerguntas = questionario.value?.perguntas?.length || 0;
    const totalRespostas = Object.keys(respostas.value).length;
    return totalPerguntas > 0 && totalRespostas === totalPerguntas;
});


// --- Lógica de Submissão ---
const abrirDialogoConfirmacao = () => {
    if (!isFormValid.value) {
        notificationStore.showError("Por favor, responda a todas as perguntas do questionário.");
        return;
    }
    isConfirmDialogOpen.value = true;
};

const confirmarSubmissao = async () => {
    isSubmitting.value = true;
    isConfirmDialogOpen.value = false;
    try {
        let payload;
        let endpoint;

        if (!isAluno.value) {
            // --- Avaliador Oficial ---
            const respostasArray = Object.keys(respostas.value).map(id_pergunta => ({
                id_pergunta: parseInt(id_pergunta),
                valor_resposta: respostas.value[id_pergunta]
            }));

            // ✅ CORREÇÃO CRUCIAL: O payload agora envia o ID da atribuição
            payload = {
                id_avaliador_projeto: atribuicao.value.id, // O ID da "tarefa de avaliação"
                nota_geral: notaGeral.value,
                observacoes: observacoes.value || 'Nenhuma observação.',
                respostas: respostasArray
            };
            endpoint = '/avaliacoes'; // Endpoint do AvaliacaoAprendizagemController

        } else {
            // --- Aluno (Voto Popular) ---
            payload = { 
                id_projeto: projeto.value.id_projeto,
                id_usuario: authStore.user.id_usuario
            };
            endpoint = '/votos_populares'; // Endpoint do VotoPopularController
        }
        
        const response = await api.post(endpoint, payload);
        
        notificationStore.showSuccess(response.data.message || 'Operação realizada com sucesso!');
        router.push('/home'); // Volta para a página inicial após avaliar

    } catch (error) {
        console.error("Erro ao submeter avaliação:", error);
        notificationStore.showError(error.response?.data?.erro || "Falha ao submeter a avaliação.");
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
  <div class="bg-grey-lighten-5">
    <v-container class="py-10">
      <v-btn variant="text" prepend-icon="mdi-arrow-left" @click="router.go(-1)" class="mb-8">
        Voltar
      </v-btn>

      <div v-if="loading" class="text-center pa-16">
        <v-progress-circular indeterminate color="green-darken-3" size="64" />
        <p class="mt-4 text-grey-darken-1">A preparar formulário...</p>
      </div>
      <v-alert v-else-if="erro" type="error" variant="tonal" prominent>{{ erro }}</v-alert>

      <div v-else-if="projeto">
        <!-- Cabeçalho da Página -->
        <v-card class="mb-8 text-center pa-4" color="green-darken-4" theme="dark">
          <v-card-subtitle>Formulário de Avaliação</v-card-subtitle>
          <v-card-title class="text-h4 font-weight-bold text-wrap">{{ projeto.titulo }}</v-card-title>
        </v-card>

        <!-- Formulário para Avaliador Oficial -->
        <v-card v-if="!isAluno" variant="flat" border>
            <v-card-text class="pa-6">
                <p class="text-h6 font-weight-regular text-medium-emphasis mb-6">Preencha os campos abaixo de acordo com a sua análise do projeto.</p>
                
                <v-expansion-panels variant="accordion">
                    <v-expansion-panel v-for="(perguntas, criterio) in perguntasAgrupadas" :key="criterio">
                        <v-expansion-panel-title class="text-h6 font-weight-medium text-green-darken-4">
                            {{ criterio }}
                        </v-expansion-panel-title>
                        <v-expansion-panel-text class="pa-6 bg-grey-lighten-5">
                            <div v-for="pergunta in perguntas" :key="pergunta.id_pergunta" class="mb-6">
                                <p class="mb-2 font-weight-medium">{{ pergunta.texto_pergunta }}</p>
                                <v-radio-group v-model="respostas[pergunta.id_pergunta]" inline hide-details>
                                    <v-radio label="Insuficiente (0)" :value="0" color="red"></v-radio>
                                    <v-radio label="Regular (50)" :value="50" color="orange"></v-radio>
                                    <v-radio label="Excelente (100)" :value="100" color="green"></v-radio>
                                </v-radio-group>
                            </div>
                        </v-expansion-panel-text>
                    </v-expansion-panel>
                </v-expansion-panels>

                <v-divider class="my-8"></v-divider>
                <h2 class="text-h6 font-weight-medium text-green-darken-4 mb-4">Considerações Finais</h2>
                <v-textarea v-model="observacoes" label="Observações textuais" rows="4" variant="outlined" class="mb-6"></v-textarea>
                <v-slider v-model="notaGeral" label="Nota Geral" thumb-label="always" step="5" min="0" max="100" color="green-darken-2"></v-slider>
            </v-card-text>
        </v-card>
        
        <!-- Mensagem para Aluno (Voto Popular) -->
        <v-card v-else variant="flat" border>
            <v-card-text class="text-center pa-10">
                <v-icon size="80" class="mb-4" color="green-darken-2">mdi-vote</v-icon>
                <p class="text-h4 font-weight-bold text-green-darken-4">Voto Popular</p>
                <p class="text-body-1 mt-2 text-medium-emphasis">O seu voto de apoio para este projeto será contabilizado. Clique no botão abaixo para confirmar.</p>
            </v-card-text>
        </v-card>

        <v-btn
            :loading="isSubmitting"
            @click="abrirDialogoConfirmacao"
            color="green-darken-3"
            size="x-large"
            block
            class="mt-8"
        >
            <v-icon start>{{ isAluno ? 'mdi-check-circle-outline' : 'mdi-send-outline' }}</v-icon>
            {{ isAluno ? 'Confirmar Voto' : 'Submeter Avaliação Oficial' }}
        </v-btn>
      </div>

      <!-- Diálogo de Confirmação -->
      <v-dialog v-model="isConfirmDialogOpen" max-width="500px" persistent>
        <v-card>
            <v-card-title class="text-h5">Confirmar Submissão</v-card-title>
            <v-card-text>
                Tem a certeza de que deseja submeter esta {{ isAluno ? 'votação' : 'avaliação' }}? Esta ação não pode ser desfeita.
            </v-card-text>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="grey-darken-1" variant="text" @click="isConfirmDialogOpen = false">Cancelar</v-btn>
                <v-btn color="green-darken-2" variant="flat" @click="confirmarSubmissao">Confirmar e Enviar</v-btn>
            </v-card-actions>
        </v-card>
      </v-dialog>

    </v-container>
  </div>
</template>

<style scoped>
.text-wrap {
    white-space: normal;
}
</style>


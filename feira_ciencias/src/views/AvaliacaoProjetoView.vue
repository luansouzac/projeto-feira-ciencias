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
const loading = ref(true);
const erro = ref(null);
const isSubmitting = ref(false);

// --- Estado do Formulário ---
const respostas = ref({}); // Ex: { 1: 50, 2: 100, ... }
const observacoes = ref('');
const notaGeral = ref(75); // Valor inicial para o slider

// --- Busca de Dados ---
onMounted(async () => {
  const projetoId = route.params.id;
  try {
    // Busca os dados do projeto e o questionário ativo em paralelo
    const [projetoResponse, questionarioResponse] = await Promise.all([
      api.get(`/projetos/${projetoId}`),
      api.get('/questionarios?ativo=true') // Assumindo que queremos o primeiro questionário ativo
    ]);

    projeto.value = projetoResponse.data;
    
    // Pega o primeiro questionário ativo da lista (pode ser ajustado se houver mais de um)
    if (questionarioResponse.data && questionarioResponse.data.length > 0) {
      questionario.value = questionarioResponse.data[0];
    } else {
      throw new Error("Nenhum questionário de avaliação ativo foi encontrado.");
    }

  } catch (error) {
    console.error("Erro ao carregar dados da página:", error);
    erro.value = error.message || "Não foi possível carregar os dados para a avaliação.";
  } finally {
    loading.value = false;
  }
});

// --- Propriedades Computadas ---
const isAluno = computed(() => authStore.user?.id_tipo_usuario === 2);

// Agrupa as perguntas do questionário por critério para facilitar a renderização
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

// --- Lógica de Submissão ---
const submeterAvaliacao = async () => {
    isSubmitting.value = true;
    try {
        let payload = { id_projeto: projeto.value.id_projeto };

        // Se for um Avaliador Oficial
        if (!isAluno.value) {
            // Transforma o objeto de respostas num array para a API
            const respostasArray = Object.keys(respostas.value).map(id_pergunta => ({
                id_pergunta: parseInt(id_pergunta),
                valor_resposta: respostas.value[id_pergunta]
            }));

            // Validação simples no frontend
            if (respostasArray.length !== questionario.value.perguntas.length) {
                notificationStore.showError("Por favor, responda a todas as perguntas.");
                isSubmitting.value = false;
                return;
            }

            payload = {
                ...payload,
                id_avaliador: authStore.user.id_usuario,
                nota_geral: notaGeral.value,
                observacoes: observacoes.value || 'Nenhuma observação.',
                respostas: respostasArray
            };
        }

        // A mesma rota é chamada para ambos os tipos de utilizador
        const response = await api.post('/avaliacoes/submeter', payload);
        
        notificationStore.showSuccess(response.data.mensagem || 'Operação realizada com sucesso!');
        router.push(`/projeto/${projeto.value.id_projeto}`); // Volta para a página pública do projeto

    } catch (error) {
        console.error("Erro ao submeter avaliação:", error);
        notificationStore.showError(error.response?.data?.erro || "Falha ao submeter a avaliação.");
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <v-container>
        <v-btn variant="text" prepend-icon="mdi-arrow-left" @click="router.go(-1)" class="mb-8">
            Voltar
        </v-btn>

        <div v-if="loading" class="text-center pa-16">
            <v-progress-circular indeterminate color="green-darken-3" size="64" />
            <p class="mt-4 text-grey-darken-1">A preparar formulário...</p>
        </div>
        <v-alert v-else-if="erro" type="error" variant="tonal" prominent>{{ erro }}</v-alert>

        <div v-else-if="projeto">
            <h1 class="text-h4 font-weight-bold">Formulário de Avaliação</h1>
            <p class="text-h6 font-weight-light text-medium-emphasis mb-8">{{ projeto.titulo }}</p>

            <!-- Formulário para Avaliador Oficial (Professor/Avaliador) -->
            <v-card v-if="!isAluno" variant="outlined">
                <v-card-text class="pa-6">
                    <div v-for="(perguntas, criterio) in perguntasAgrupadas" :key="criterio" class="mb-8">
                        <h2 class="text-h6 font-weight-medium text-green-darken-4 mb-4">{{ criterio }}</h2>
                        <div v-for="pergunta in perguntas" :key="pergunta.id_pergunta" class="mb-6">
                            <p class="mb-2">{{ pergunta.texto_pergunta }}</p>
                            <v-radio-group v-model="respostas[pergunta.id_pergunta]" inline>
                                <v-radio label="Insuficiente (0)" :value="0"></v-radio>
                                <v-radio label="Regular (50)" :value="50"></v-radio>
                                <v-radio label="Excelente (100)" :value="100"></v-radio>
                            </v-radio-group>
                        </div>
                    </div>
                    <v-divider class="my-8"></v-divider>
                    <h2 class="text-h6 font-weight-medium text-green-darken-4 mb-4">Considerações Finais</h2>
                    <v-textarea v-model="observacoes" label="Observações textuais" rows="4" variant="outlined" class="mb-6"></v-textarea>
                    <v-slider v-model="notaGeral" label="Nota Geral" thumb-label step="5" min="0" max="100" color="green-darken-2"></v-slider>
                </v-card-text>
            </v-card>
            
            <!-- Mensagem para Aluno (Voto Popular) -->
            <v-card v-else variant="tonal" color="green">
                <v-card-text class="text-center pa-8">
                    <v-icon size="64" class="mb-4">mdi-vote-outline</v-icon>
                    <p class="text-h5">Registar Voto Popular</p>
                    <p>O seu voto de apoio para este projeto será contabilizado. Clique no botão abaixo para confirmar.</p>
                </v-card-text>
            </v-card>

            <v-btn
                :loading="isSubmitting"
                @click="submeterAvaliacao"
                color="green-darken-3"
                size="x-large"
                block
                class="mt-8"
            >
                <v-icon start>{{ isAluno ? 'mdi-check-circle-outline' : 'mdi-send-outline' }}</v-icon>
                {{ isAluno ? 'Confirmar Voto' : 'Submeter Avaliação Oficial' }}
            </v-btn>
        </div>
    </v-container>
</template>


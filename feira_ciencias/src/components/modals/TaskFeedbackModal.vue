<script setup>
import { ref, watch, defineProps, defineEmits } from 'vue'
import api from '@/assets/plugins/axios.js'

const props = defineProps({
  modelValue: { type: Boolean, default: false }, // para v-model
  task: { type: Object, default: null },
  isProfessor: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'feedback-sent'])

// --- Estado Interno do Modal ---
const events = ref([])
const isLoading = ref(false)
const error = ref(null)
const newFeedbackText = ref('')
const isSendingFeedback = ref(false)

// --- Funções Utilitárias ---
const getMemberNameById = (userId) => {
  // A prop 'task' pode conter a equipe, mas é mais seguro assumir que não
  // Idealmente, o backend deveria retornar o nome do autor
  return `Usuário ID: ${userId}` 
}
const getFullStorageUrl = (filePath) => {
  if (!filePath) return null
  return `${import.meta.env.VITE_API_BASE_URL}/storage/${filePath}`
}
const formatDate = (dateString) => {
  if (!dateString) return 'Data indefinida'
  return new Date(dateString).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' })
}

// --- Lógica Principal ---
const fetchHistory = async () => {
  if (!props.task) return
  isLoading.value = true
  error.value = null
  try {
    const [feedbackResponse, registrationResponse] = await Promise.all([
      api.get(`/tarefas/${props.task.id_tarefa}/feedbacks`),
      api.get(`/registros_tarefas?id_tarefa=${props.task.id_tarefa}`),
    ])

    const feedbacks = (feedbackResponse.data.data || feedbackResponse.data || []).map(fb => ({
      ...fb, type: 'feedback', date: new Date(fb.created_at)
    }))
    
    const submissions = (registrationResponse.data.data || registrationResponse.data || [])
      .filter(reg => reg.resultado || reg.arquivo)
      .map(reg => ({
        ...reg, type: 'submission', date: new Date(reg.data_execucao),
        feedback: reg.resultado,
        // O backend idealmente já retornaria o nome do usuário
        usuario: { nome: reg.responsavel?.nome || getMemberNameById(reg.id_responsavel) }
      }))

    events.value = [...feedbacks, ...submissions].sort((a, b) => b.date - a.date)
  } catch (err) {
    console.error('Erro ao buscar histórico da tarefa:', err)
    error.value = 'Não foi possível carregar o histórico. Tente novamente.'
  } finally {
    isLoading.value = false
  }
}

const handleSendFeedback = async () => {
  if (!newFeedbackText.value.trim() || !props.task) return
  isSendingFeedback.value = true
  try {
    const payload = { feedback: newFeedbackText.value }
    const response = await api.post(`/tarefas/${props.task.id_tarefa}/feedbacks`, payload)
    
    const newFeedbackFromServer = response.data.data || response.data
    
    // Emite um evento para o pai saber que um novo feedback foi adicionado
    emit('feedback-sent', newFeedbackFromServer)

    // Adiciona o novo feedback instantaneamente à lista do modal
    events.value.unshift({
      ...newFeedbackFromServer,
      type: 'feedback',
      date: new Date(newFeedbackFromServer.created_at)
    })
    newFeedbackText.value = ''
  } catch (err) {
    console.error('Erro ao enviar feedback:', err)
    // idealmente, mostrar erro com a store de notificação
  } finally {
    isSendingFeedback.value = false
  }
}

// Observa a abertura do modal para buscar o histórico
watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    fetchHistory()
  } else {
    // Limpa o estado quando o modal fecha
    events.value = []
    newFeedbackText.value = ''
  }
})

const close = () => emit('update:modelValue', false)
</script>

<template>
  <v-dialog :model-value="modelValue" @update:model-value="close" max-width="700px" persistent>
    <v-card class="d-flex flex-column" style="max-height: 90vh">
      <v-card-title class="d-flex align-center text-h5 bg-green-darken-3 text-white">
        <v-icon start>mdi-comment-multiple-outline</v-icon>
        Feedbacks da Tarefa
        <v-spacer></v-spacer>
        <v-btn icon="mdi-close" variant="text" @click="close"></v-btn>
      </v-card-title>
      <v-card-subtitle class="bg-green-darken-3 text-white pb-3">
        "{{ task?.descricao }}"
      </v-card-subtitle>

      <v-card-text class="flex-grow-1 pt-6" style="overflow-y: auto">
        <div v-if="isLoading" class="text-center py-8">
          <v-progress-circular indeterminate color="green-darken-2" size="48"></v-progress-circular>
          <p class="mt-3 text-grey-darken-1">Buscando histórico...</p>
        </div>
        <v-alert v-else-if="error" type="error" variant="tonal">{{ error }}</v-alert>
        <div v-else>
          <div v-if="!events.length" class="text-center pa-8 text-grey-darken-1">
            <v-icon size="48" class="mb-4">mdi-comment-remove-outline</v-icon>
            <p>Nenhum feedback ou entrega registrada para esta tarefa.</p>
          </div>
          <v-timeline v-else side="end" align="start" density="compact">
            <v-timeline-item
              v-for="event in events"
              :key="`${event.type}-${event.id_feedback || event.id_registro_tarefa}`"
              :dot-color="event.type === 'submission' ? 'purple-darken-1' : 'green-darken-1'"
              :icon="event.type === 'submission' ? 'mdi-upload' : 'mdi-comment-processing-outline'"
              size="small"
              class="pb-2"
            >
              <v-sheet rounded="lg" border class="pa-3 bg-grey-lighten-5">
                <p class="text-body-1 font-italic">"{{ event.feedback || 'Nenhum comentário.' }}"</p>
                <div v-if="event.type === 'submission' && event.arquivo" class="mt-3">
                  <v-btn :href="getFullStorageUrl(event.arquivo)" target="_blank">Ver Anexo</v-btn>
                </div>
                <div class="text-caption text-grey-darken-1 mt-3 text-right">
                  - {{ event.usuario?.nome }} em {{ formatDate(event.date) }}
                </div>
              </v-sheet>
            </v-timeline-item>
          </v-timeline>
        </div>
      </v-card-text>

      <template v-if="isProfessor">
        <v-divider></v-divider>
        <div class="pa-4 bg-grey-lighten-4">
          <h4 class="text-subtitle-1 font-weight-medium mb-3">Enviar Novo Feedback</h4>
          <v-textarea v-model="newFeedbackText" ></v-textarea>
        </div>
      </template>

      <v-card-actions class="pa-4 bg-grey-lighten-5">
        <v-spacer></v-spacer>
        <v-btn
          v-if="isProfessor"
          color="green-darken-2"
          variant="flat"
          @click="handleSendFeedback"
          :loading="isSendingFeedback"
          :disabled="!newFeedbackText.trim()"
          prepend-icon="mdi-send"
        >
          Enviar Feedback
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
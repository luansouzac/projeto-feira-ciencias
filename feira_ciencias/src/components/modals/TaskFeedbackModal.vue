<script setup>
import { ref, watch, defineProps, defineEmits } from 'vue'
import api from '@/assets/plugins/axios.js'
import { useNotificationStore } from '@/stores/notification';
const notificationStore = useNotificationStore()

const props = defineProps({
  modelValue: { type: Boolean, default: false }, // para v-model
  task: { type: Object, default: null },
  canInteract: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'feedback-sent'])

// --- Estado Interno do Modal ---
const events = ref([])
const isLoading = ref(false)
const error = ref(null)
const newText = ref('')
const isSending = ref(false)

const feedbackRules = [
  (v) => !!v || 'O feedback não pode ficar em branco.',
  (v) => (v && v.length >= 5) || 'O feedback deve ter no mínimo 5 caracteres.',
]

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

    const feedbacks = (feedbackResponse.data || []).map(fb => ({
      ...fb,
      type: fb.usuario?.id_tipo_usuario === 4 ? 'feedback' : 'comment',
      date: new Date(fb.created_at)
    }));
    
    const submissions = (registrationResponse.data || []).filter(reg => reg.resultado || reg.arquivo).map(reg => ({
      ...reg, type: 'submission', date: new Date(reg.data_execucao), feedback: reg.resultado,
      usuario: { nome: reg.responsavel?.nome || 'Usuário desconhecido' }
    }));

    events.value = [...feedbacks, ...submissions].sort((a, b) => b.date - a.date)
  } catch (err) {
    console.error('Erro ao buscar histórico da tarefa:', err)
    error.value = 'Não foi possível carregar o histórico. Tente novamente.'
  } finally {
    isLoading.value = false
  }
}

const handleSend = async () => {
  if (!newText.value.trim() || !props.task) return
  isSending.value = true
  try {
    const payload = { feedback: newText.value }
    const response = await api.post(`/tarefas/${props.task.id_tarefa}/feedbacks`, payload)

    
    emit('message-sent', response.data || response.data)
    notificationStore.showSuccess('Sua mensagem foi enviada!')
    
    const newItem = response.data || response.data

    events.value.unshift({
      ...newItem,
      type: newItem.usuario?.id_tipo_usuario === 4 ? 'feedback' : 'comment',
      date: new Date(newItem.created_at)
    })
    newText.value = ''
  } catch (err) {
    notificationStore.showError('Não foi possível enviar a mensagem.')
  } finally {
    isSending.value = false
  }
}

// Observa a abertura do modal para buscar o histórico
watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) {
      fetchHistory()
    } else {
      // Limpa o estado quando o modal fecha
      events.value = []
      newText.value = ''
    }
  },
)

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
                <p class="text-body-1 font-italic">
                  "{{ event.feedback || 'Nenhum comentário.' }}"
                </p>
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

      <template v-if="canInteract">
        <v-divider></v-divider>
        <div class="pa-4 bg-grey-lighten-4">
          <h4 class="text-subtitle-1 font-weight-medium mb-3">Enviar Novo Feedback</h4>
          <v-textarea
            v-model="newText"
            label="Escreva seu feedback aqui"
            variant="outlined"
            rows="3"
            auto-grow
            counter
            hint="Mínimo de 5 caracteres"
            persistent-hint
            :rules="feedbackRules"
            :disabled="isSending"
            bg-color="white"
          ></v-textarea>
        </div>
      </template>

      <v-card-actions class="pa-4 bg-grey-lighten-5">
        <v-spacer></v-spacer>
        <v-btn
          v-if="canInteract"
          color="green-darken-2"
          variant="flat"
          @click="handleSend"
          :loading="isSending"
          :disabled="newText.trim().length < 5"
          prepend-icon="mdi-send"
        >
          Enviar Feedback
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

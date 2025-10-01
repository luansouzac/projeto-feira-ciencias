<script setup>
import { ref, watch, defineProps, defineEmits } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  task: { type: Object, default: null },
  isLoading: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'submit'])

// Estado interno do formulário de submissão
const submissionData = ref({
  resultado: '',
  arquivo: null,
})

// Reseta o formulário quando o modal abre
watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    submissionData.value = { resultado: '', arquivo: null }
  }
})

const handleFileChange = (files) => {
  submissionData.value.arquivo = files[0] || null
}

const submit = () => {
  // Emite o evento 'submit' com os dados para o pai
  emit('submit', submissionData.value)
}

const close = () => {
  emit('update:modelValue', false)
}

</script>

<template>
  <v-dialog :model-value="modelValue" @update:model-value="close" persistent max-width="700px">
    <v-card>
      <v-card-title class="d-flex align-center text-h5 bg-green-darken-3 text-white">
        <v-icon start>mdi-check-circle-outline</v-icon>
        Entregar Tarefa
        <v-spacer></v-spacer>
        <v-btn icon="mdi-close" variant="text" @click="close"></v-btn>
      </v-card-title>
      <v-card-subtitle class="bg-green-darken-3 text-white pb-2">
        "{{ task?.descricao }}"
      </v-card-subtitle>
      <v-card-text class="pt-6">
        <v-form @submit.prevent="submit">
          <v-textarea
            v-model="submissionData.resultado"
            label="Comentário da Entrega (Opcional)"
            placeholder="Descreva o que foi feito ou deixe um comentário para o orientador."
            variant="outlined"
            rows="4"
            autofocus
          ></v-textarea>
          <v-file-input
            @update:modelValue="handleFileChange"
            label="Anexar Arquivo (Opcional)"
            variant="outlined"
            prepend-icon="mdi-paperclip"
            accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt"
            class="mt-4"
            clearable
          ></v-file-input>
        </v-form>
      </v-card-text>
      <v-card-actions class="pa-4">
        <v-spacer></v-spacer>
        <v-btn color="grey-darken-1" variant="text" @click="close" :disabled="isLoading">Cancelar</v-btn>
        <v-btn color="green-darken-2" variant="flat" @click="submit" :loading="isLoading">
          Confirmar Entrega
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
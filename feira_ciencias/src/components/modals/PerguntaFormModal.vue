<script setup>
import { ref, watch } from 'vue'; 

const props = defineProps({
  modelValue: Boolean,
  questionarioId: Number,
  isLoading: Boolean,
  perguntaToEdit: Object,
});
const emit = defineEmits(['update:modelValue', 'save']);

const form = ref(null);
const formData = ref({ texto_pergunta: '', criterio: '' });
const criterios = [
  'Adequação ao tema',
  'Domínio do conteúdo',
  'Organização e clareza',
  'Criatividade',
  'Trabalho em equipe'
];

const dialogTitle = ref('Adicionar Pergunta');
const saveButtonText = ref('Adicionar Pergunta');

watch(() => props.modelValue, (isOpening) => {
    if (isOpening) {
        if (props.perguntaToEdit) {
            dialogTitle.value = 'Editar Pergunta';
            saveButtonText.value = 'Salvar Alterações';
            formData.value = { ...props.perguntaToEdit };
        } else {
            dialogTitle.value = 'Adicionar Pergunta';
            saveButtonText.value = 'Adicionar Pergunta';
            formData.value = { texto_pergunta: '', criterio: '' };
        }
    }
});

const close = () => {
    emit('update:modelValue', false);
};

const save = async () => {
  const { valid } = await form.value.validate();
  if (valid) {
    emit('save', { ...formData.value, id_questionario: props.questionarioId });
  }
};
</script>

<template>
  <v-dialog :model-value="modelValue" @update:modelValue="close" persistent max-width="600px">
    <v-card>
      <v-card-title class="bg-green-darken-3">
        <span class="text-h5">{{ dialogTitle }}</span>
      </v-card-title>
      <v-card-text class="pt-6">
        <v-form ref="form">
          <v-textarea
            v-model="formData.texto_pergunta"
            label="Texto da Pergunta"
            :rules="[v => !!v || 'O texto da pergunta é obrigatório']"
            variant="outlined"
            rows="3"
            class="mb-4"
          ></v-textarea>
          <v-combobox
            v-model="formData.criterio"
            :items="criterios"
            label="Critério de Avaliação"
            :rules="[v => !!v || 'O critério é obrigatório']"
            variant="outlined"
            hint="Selecione um critério existente ou digite um novo"
          ></v-combobox>
        </v-form>
      </v-card-text>
      <v-card-actions class="pa-4">
        <v-spacer></v-spacer>
        <v-btn color="grey-darken-1" variant="text" @click="close">Cancelar</v-btn>
        <v-btn color="green-darken-2" variant="flat" @click="save" :loading="isLoading">
          {{ saveButtonText }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>


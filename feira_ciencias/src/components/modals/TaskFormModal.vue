<script setup>
import { ref, watch, computed, defineProps, defineEmits } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false }, // para v-model
  taskToEdit: { type: Object, default: null },
  membros: { type: Array, default: () => [] },
  isLoading: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'save'])

const taskFormData = ref({})

const taskModalTitle = computed(() => (props.taskToEdit ? 'Editar Tarefa' : 'Nova Tarefa'))

watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    if (props.taskToEdit) {
      taskFormData.value = { ...props.taskToEdit };
    } else {
      taskFormData.value = {
        descricao: '',
        detalhe: '',
        data_inicio_prevista: null,
        data_fim_prevista: null,
        id_usuario_atribuido: null,
      };
    }
  }
});


const save = () => {
  emit('save', taskFormData.value)
}

const close = () => {
  emit('update:modelValue', false)
}
</script>

<template>
  <v-dialog :model-value="modelValue" @update:model-value="close" persistent max-width="700px">
    <v-card>
      <v-card-title class="d-flex align-center text-h5 bg-green-darken-3 text-white">
        {{ taskModalTitle }}
        <v-spacer></v-spacer>
        <v-btn icon="mdi-close" variant="text" @click="close"></v-btn>
      </v-card-title>
      <v-card-text class="pt-6">
        <v-form>
          <v-text-field
            v-model="taskFormData.descricao"
            label="Título da Tarefa"
            variant="outlined"
            :rules="[(v) => !!v || 'O título é obrigatório']"
            autofocus
          ></v-text-field>
          <v-textarea
            v-model="taskFormData.detalhe"
            label="Descrição Detalhada (Opcional)"
            variant="outlined"
            rows="3"
            class="mt-4"
          ></v-textarea>
          <v-select
            v-model="taskFormData.id_usuario_atribuido"
            :items="membros"
            item-title="usuario.nome"
            item-value="id_usuario"
            label="Atribuir a (Opcional)"
            variant="outlined"
            class="mt-4"
            clearable
          ></v-select>
          <v-row class="mt-2">
            <v-col cols="12" md="6">
              <v-text-field
                v-model="taskFormData.data_inicio_prevista"
                label="Início Previsto"
                type="date"
                variant="outlined"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="taskFormData.data_fim_prevista"
                label="Fim Previsto"
                type="date"
                variant="outlined"
              ></v-text-field>
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>
      <v-card-actions class="pa-4">
        <v-spacer></v-spacer>
        <v-btn color="grey-darken-1" variant="text" @click="close" :disabled="isLoading">Cancelar</v-btn>
        <v-btn color="green-darken-2" variant="flat" @click="save" :loading="isLoading">Salvar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
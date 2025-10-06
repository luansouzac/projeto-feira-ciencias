<script setup>
import { defineProps, defineEmits } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  itemName: { type: String, default: '' },
  title: { type: String, default: 'Confirmar Exclusão' },
  message: { type: String, default: 'Esta ação não pode ser desfeita.' },
})

const emit = defineEmits(['update:modelValue', 'confirm'])

const close = () => emit('update:modelValue', false)
const confirm = () => {
  emit('confirm')
}
</script>

<template>
  <v-dialog :model-value="modelValue" @update:model-value="close" persistent max-width="500px">
    <v-card>
      <v-card-title class="text-h5">
        <v-icon color="red" start>mdi-alert-circle-outline</v-icon>
        {{ title }}
      </v-card-title>
      <v-card-text>
        Você tem certeza que deseja apagar
        <strong v-if="itemName">"{{ itemName }}"</strong>?
        <br /><br />
        {{ message }}
      </v-card-text>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn color="grey-darken-1" variant="text" @click="close">Cancelar</v-btn>
        <v-btn color="red-darken-1" variant="flat" @click="confirm">Apagar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
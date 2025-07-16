<script setup>
import { ref, watch } from 'vue';

// --- Props (Configuração que o modal recebe do pai) ---
const props = defineProps({
  // Controla a visibilidade do modal (usa v-model)
  modelValue: {
    type: Boolean,
    required: true,
  },
  // O título do modal (ex: "Novo Projeto", "Editar Tarefa")
  title: {
    type: String,
    default: 'Formulário',
  },
  // O item a ser editado. Se for null, é um formulário de criação.
  item: {
    type: Object,
    default: null,
  },
  // A configuração dos campos do formulário
  fields: {
    type: Array,
    required: true,
  },
  // Estado de carregamento para o botão de salvar
  loading: {
    type: Boolean,
    default: false,
  },
});

// --- Emits (Eventos que o modal envia para o pai) ---
const emit = defineEmits(['update:modelValue', 'save']);

// --- Estado Interno ---
const formData = ref({});
const formRef = ref(null); // Referência para o v-form para validação

// --- Lógica ---

// Observa mudanças no 'item' que vem do pai.
// Isso acontece quando o modal é aberto para edição.
watch(() => props.item, (newItem) => {
  if (newItem) {
    // Modo Edição: Preenche o formulário com os dados do item
    formData.value = { ...newItem };
  } else {
    // Modo Criação: Reseta o formulário
    formData.value = {};
  }
}, { immediate: true }); // 'immediate' garante que rode na primeira vez

// Função para fechar o modal
const closeModal = () => {
  emit('update:modelValue', false);
};

// Função para lidar com o clique no botão Salvar
const onSave = async () => {
  if (!formRef.value) return;
  // Valida o formulário do Vuetify
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  // Emite o evento 'save' com os dados do formulário para o componente pai
  emit('save', formData.value);
};
</script>

<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="closeModal"
    max-width="700px"
    persistent
    scrollable
  >
    <v-card>
      <v-card-title class="pa-4">
        <span class="text-h5">{{ title }}</span>
      </v-card-title>

      <v-card-text class="pa-4">
        <v-form ref="formRef" @submit.prevent="onSave">
          <!-- Loop para criar os campos do formulário dinamicamente -->
          <div v-for="field in fields" :key="field.key">
            
            <!-- Campo de Texto -->
            <v-text-field
              v-if="field.type === 'text'"
              v-model="formData[field.key]"
              :label="field.label"
              :rules="field.rules"
              variant="outlined"
              class="mb-3"
            ></v-text-field>

            <!-- Área de Texto -->
            <v-textarea
              v-if="field.type === 'textarea'"
              v-model="formData[field.key]"
              :label="field.label"
              :rules="field.rules"
              variant="outlined"
              class="mb-3"
            ></v-textarea>

            <!-- Campo de Seleção (Select) -->
            <v-select
              v-if="field.type === 'select'"
              v-model="formData[field.key]"
              :label="field.label"
              :items="field.options"
              :rules="field.rules"
              item-title="text"
              item-value="value"
              variant="outlined"
              class="mb-3"
            ></v-select>

            <!-- Campo checkbox -->
            <v-checkbox
              v-if="field.type === 'checkbox'"
              v-model="formData[field.key]"
              :label="field.label"
              :rules="field.rules"
              :value="field.value"
              class="mb-3"
            ></v-checkbox>

            <!-- Adicione outros tipos de campo aqui (ex: v-checkbox, v-autocomplete) -->

          </div>
        </v-form>
      </v-card-text>

      <v-divider></v-divider>

      <v-card-actions class="pa-4">
        <v-spacer></v-spacer>
        <v-btn
          color="grey-darken-1"
          variant="text"
          @click="closeModal"
          :disabled="loading"
        >
          Cancelar
        </v-btn>
        <v-btn
          color="green-darken-3"
          variant="flat"
          :loading="loading"
          @click="onSave"
        >
          Salvar
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

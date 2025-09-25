<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="closeModal"
    max-width="800px"
    scrollable
  >
    <v-card rounded="lg" elevation="8">
      <v-card-title class="d-flex align-center pa-4">
        <v-icon start color="green-darken-4" icon="mdi-form-select"></v-icon>
        <span class="text-h6 font-weight-bold" :style="{ color: 'var(--v-theme-green-darken-4)' }">
          {{ title }}
        </span>
        <v-spacer></v-spacer>
        <v-btn
          icon="mdi-close"
          variant="text"
          @click="closeModal"
          aria-label="Fechar modal"
        ></v-btn>
      </v-card-title>

      <v-divider />

      <v-card-text class="pa-4">
        <v-form ref="formRef" @submit.prevent="onSave">
          <v-container>
            <v-row>
              <v-col
                v-for="field in fields"
                :key="field.key"
                :cols="field.cols || 12"
                :md="field.md || field.cols || 12"
                class="py-1"
              >
                <input
                  v-if="['id'].includes(field.type)"
                  v-model="formData[field.key]"
                  :name="field.key"
                  type="hidden"
                >

                <v-text-field
                  v-if="['text', 'number', 'datetime-local'].includes(field.type)"
                  v-model="formData[field.key]"
                  :label="field.label"
                  :type="field.type === 'number' ? 'number' : field.type"
                  :rules="getValidationRules(field)"
                  variant="outlined"
                  color="green-darken-4"
                  density="comfortable"
                ></v-text-field>

                <v-textarea
                  v-if="field.type === 'textarea'"
                  v-model="formData[field.key]"
                  :label="field.label"
                  :rules="getValidationRules(field)"
                  variant="outlined"
                  color="green-darken-4"
                  density="comfortable"
                  auto-grow
                  rows="3"
                ></v-textarea>

                <v-select
                  v-if="field.type === 'select'"
                  v-model="formData[field.key]"
                  :items="field.items"
                  :label="field.label"
                  :rules="getValidationRules(field)"
                  item-title="title"
                  item-value="value"
                  variant="outlined"
                  color="green-darken-4"
                  density="comfortable"
                ></v-select>

                <v-menu
                  v-if="field.type === 'date'"
                  v-model="menuState[field.key]"
                  :close-on-content-click="false"
                  location="bottom"
                >
                  <template v-slot:activator="{ props: menuProps }">
                    <v-text-field
                      :model-value="formatDateForDisplay(formData[field.key])"
                      :label="field.label"
                      :rules="getValidationRules(field)"
                      readonly
                      v-bind="menuProps"
                      variant="outlined"
                      color="green-darken-4"
                      density="comfortable"
                      append-inner-icon="mdi-calendar"
                    ></v-text-field>
                  </template>
                  <v-date-picker
                    v-model="formData[field.key]"
                    @update:model-value="menuState[field.key] = false"
                    title=""
                    header="Selecione a data"
                    color="green-darken-4"
                  ></v-date-picker>
                </v-menu>

                <v-checkbox
                  v-if="field.type === 'checkbox'"
                  v-model="formData[field.key]"
                  :label="field.label"
                  :rules="getValidationRules(field)"
                  color="green-darken-4"
                  :true-value="1"
                  :false-value="0"
                ></v-checkbox>
                
              </v-col>
            </v-row>
          </v-container>
        </v-form>
      </v-card-text>

      <v-divider />

      <v-card-actions class="pa-4">
        <v-spacer></v-spacer>
        <v-btn variant="text" @click="closeModal" :disabled="loading">
          Cancelar
        </v-btn>
        <v-btn
          color="green-darken-4"
          variant="flat"
          :loading="loading"
          @click="onSave"
          prepend-icon="mdi-check-circle"
        >
          Salvar
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, watch, nextTick, onMounted } from 'vue';

// --- PROPS E EMITS ---
const props = defineProps({
  modelValue: { type: Boolean, required: true },
  title: { type: String, default: 'Formulário' },
  item: { type: Object, default: null },
  fields: { type: Array, required: true },
  loading: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue', 'save']);

// --- ESTADO INTERNO ---
const formRef = ref(null);
const formData = ref({});
const menuState = ref({}); // Objeto para controlar o estado de cada menu de data

// --- LÓGICA DE DADOS E FORMULÁRIO ---

// Preenche ou reseta o formulário baseado nas props
watch(() => props.item, (newItem) => {
  const initialData = {};
  props.fields.forEach(field => {
    let value = null;
    if (newItem && newItem[field.key] !== undefined) {
      // Modo Edição: usa o valor do item
      value = newItem[field.key];
    } else {
      // Modo Criação: usa o valor padrão definido na config
      value = field.defaultValue;
    }

    // Converte datas (string do backend) para objetos Date para o v-date-picker
    if (field.type === 'date' && value) {
      initialData[field.key] = new Date(value);
    } else {
      initialData[field.key] = value;
    }
  });
  formData.value = initialData;
}, { immediate: true, deep: true });


// Foco automático no primeiro campo
watch(() => props.modelValue, async (isVisible) => {
  if (isVisible) {
    await nextTick();
    formRef.value?.$el.querySelector('input')?.focus();
  }
});

// --- FUNÇÕES HELPER ---

// Formata a data para exibição (dd/mm/aaaa)
const formatDateForDisplay = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('pt-BR', { timeZone: 'UTC' });
};

// Adapta as regras de validação para que tenham acesso ao `formData` completo
const getValidationRules = (field) => {
  if (!field.rules || !Array.isArray(field.rules)) return [];
  return field.rules.map(rule => {
    if (typeof rule === 'function') {
      // Cria uma nova função que passa o formData como segundo argumento
      return (value) => rule(value, formData.value);
    }
    return rule;
  });
};

// --- FUNÇÕES DE CONTROLE ---

const closeModal = () => {
  emit('update:modelValue', false);
};

const onSave = async () => {
  if (!formRef.value) return;
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  // Prepara os dados para o backend
  const dataToSend = { ...formData.value }; 
  props.fields.forEach(field => {
    // Converte objetos Date de volta para strings YYYY-MM-DD
    if (field.type === 'date' && dataToSend[field.key]) {
      const date = new Date(dataToSend[field.key]);
      // Ajuste para evitar problemas de fuso horário (pega o ano, mês e dia em UTC)
      date.setMinutes(date.getMinutes() + date.getTimezoneOffset());
      dataToSend[field.key] = date.toISOString().split('T')[0];
    }
  });

  emit('save', dataToSend);
};
</script>

<style scoped>
/* Estilo para garantir que o foco do teclado seja bem visível */
.v-btn:focus-visible,
:deep(.v-field--focused .v-field__outline) {
  box-shadow: 0 0 0 3px rgba(var(--v-theme-green-darken-4), 0.4) !important;
}
</style>
    <script setup>
    import { ref, watch, computed } from 'vue';

    const props = defineProps({
      modelValue: Boolean, // Controla a visibilidade do modal (v-model)
      questionarioToEdit: Object,
      eventos: Array,
      isLoading: Boolean,
    });

    const emit = defineEmits(['update:modelValue', 'save']);

    const form = ref(null);
    const formData = ref({});

    // Popula o formulário quando um questionário é passado para edição
    watch(() => props.questionarioToEdit, (newVal) => {
      if (newVal) {
        formData.value = { ...newVal };
      } else {
        formData.value = { titulo: '', id_evento: null, ativo: true };
      }
    });

    const dialogTitle = computed(() => 
      props.questionarioToEdit ? 'Editar Questionário' : 'Criar Novo Questionário'
    );

    const close = () => emit('update:modelValue', false);

    const save = async () => {
      const { valid } = await form.value.validate();
      if (valid) {
        emit('save', formData.value);
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
              <v-text-field
                v-model="formData.titulo"
                label="Título do Questionário"
                :rules="[v => !!v || 'O título é obrigatório']"
                variant="outlined"
                class="mb-4"
              ></v-text-field>
              <v-select
                v-model="formData.id_evento"
                :items="eventos"
                item-title="nome"
                item-value="id_evento"
                label="Associar ao Evento"
                :rules="[v => !!v || 'A associação a um evento é obrigatória']"
                variant="outlined"
              ></v-select>
            </v-form>
          </v-card-text>
          <v-card-actions class="pa-4">
            <v-spacer></v-spacer>
            <v-btn color="grey-darken-1" variant="text" @click="close">Cancelar</v-btn>
            <v-btn color="green-darken-2" variant="flat" @click="save" :loading="isLoading">Salvar</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </template>
    

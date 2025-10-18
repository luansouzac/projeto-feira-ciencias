    <script setup>
    import { ref } from 'vue';

    const props = defineProps({
      modelValue: Boolean,
      questionarioId: Number,
      isLoading: Boolean,
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

    const close = () => {
        emit('update:modelValue', false);
        formData.value = { texto_pergunta: '', criterio: '' }; // Limpa o formulário ao fechar
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
            <span class="text-h5">Adicionar Pergunta</span>
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
            <v-btn color="green-darken-2" variant="flat" @click="save" :loading="isLoading">Adicionar Pergunta</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </template>
    

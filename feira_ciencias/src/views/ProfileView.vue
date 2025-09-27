<script setup>
import { ref, onMounted } from 'vue';
import api from '../assets/plugins/axios.js'; // Usando sua instância configurada
import { useNotificationStore } from '@/stores/notification';

const notificationStore = useNotificationStore();

// 1. ESTADO DO COMPONENTE
const profileForm = ref(null); // Referência para o formulário
const carregando = ref(false);
const defaultPhoto = 'https://cdn.vuetifyjs.com/images/avatars/avatar-2.jpg';

// 'form' guarda os dados que serão editados
const form = ref({
  nome: '',
  email: '',
  id_matricula: '',
  telefone: '',
  ano: '',
  photo_url: null, // URL da foto para exibição
});

// 'photoFile' guarda o arquivo da nova foto para upload
const photoFile = ref(null);

// 2. REGRAS E FORMATAÇÃO
const rules = {
  required: v => !!v || 'Campo obrigatório.',
  email: v => /.+@.+\..+/.test(v) || 'E-mail inválido.',
};

const formatPhone = (event) => {
  let value = event.target.value.replace(/\D/g, '');
  if (value.length > 11) value = value.substring(0, 11);
  value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
  value = value.replace(/(\d{5})(\d{4})$/, '$1-$2'); // Ajustado para celular de 9 dígitos
  form.value.telefone = value;
};

// 3. LÓGICA DE CARREGAMENTO E AÇÕES
onMounted(() => {
  // Carrega os dados do usuário logado do sessionStorage
  const userDataString = sessionStorage.getItem('user_data');
  if (userDataString) {
    const userData = JSON.parse(userDataString).user;
    form.value.nome = userData.nome;
    form.value.email = userData.email;
    form.value.id_matricula = userData.id_matricula;
    form.value.telefone = userData.telefone || '';
    form.value.ano = userData.ano || ''; // <-- LINHA ADICIONADA AQUI
    form.value.photo_url = userData.photo ? `/storage/${userData.photo}` : null;
  }
});

const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (!file) return;

  photoFile.value = file;
  // Cria uma URL temporária para preview imediato da imagem
  form.value.photo_url = URL.createObjectURL(file);
};

const saveProfile = async () => {
  const { valid } = await profileForm.value.validate();
  if (!valid) {
    notificationStore.showError('Por favor, corrija os campos inválidos.');
    return;
  }

  carregando.value = true;
  const formData = new FormData();
  formData.append('nome', form.value.nome);
  formData.append('email', form.value.email);
  formData.append('id_matricula', form.value.id_matricula);
  formData.append('telefone', form.value.telefone);
  formData.append('ano', form.value.ano);

  if (photoFile.value) {
    formData.append('photo', photoFile.value);
  }
  
  formData.append('_method', 'PUT');

  try {
    const userDataString = sessionStorage.getItem('user_data');
    const userId = JSON.parse(userDataString).user.id_usuario;

    const { data } = await api.post(`/usuarios/${userId}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    
    const storedData = JSON.parse(userDataString);
    storedData.user = data;
    sessionStorage.setItem('user_data', JSON.stringify(storedData));

    notificationStore.showSuccess('Perfil atualizado com sucesso!');
    
  } catch (error) {
    console.error('Erro ao salvar perfil:', error);
    const message = error.response?.data?.message || 'Erro ao salvar o perfil.';
    notificationStore.showError(message);
  } finally {
    carregando.value = false;
  }
};
</script>

<template>
  <v-container class="py-10">
    <v-row justify="center">
      <v-col cols="12" md="8" lg="6">
        <v-card class="pa-4 pa-md-6 elevation-6 rounded-lg">
          <v-card-title class="text-h5 font-weight-bold text-green-darken-4 mb-6">
            Meu Perfil
          </v-card-title>

          <div class="d-flex flex-column align-center mb-6">
            <v-avatar size="120" class="elevation-4 border">
              <v-img :src="form.photo_url || defaultPhoto" cover />
            </v-avatar>
            <v-btn
              color="green-darken-3"  
              variant="text"
              class="mt-4"
              @click="$refs.fileInput.click()"
              prepend-icon="mdi-camera"
            >
              Alterar Foto
            </v-btn>
            <input
              type="file"
              ref="fileInput"
              class="d-none"
              accept="image/png, image/jpeg"
              @change="handleFileChange"
            />
          </div>

          <v-divider class="my-4" />

          <v-form ref="profileForm" @submit.prevent="saveProfile">
            <v-row dense>
              <v-col cols="12">
                <v-text-field
                  v-model="form.nome"
                  label="Nome Completo"
                  :rules="[rules.required]"
                  variant="outlined"
                  density="compact"
                  color="green-darken-3" 
                />
              </v-col>
              <v-col cols="12">
                <v-text-field
                  v-model="form.email"
                  label="Email"
                  type="email"
                  :rules="[rules.required, rules.email]"
                  variant="outlined"
                  density="compact"
                  color="green-darken-3" 
                />
              </v-col>

              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="form.id_matricula"
                  label="Matrícula"
                  :rules="[rules.required]"
                  variant="outlined"
                  density="compact"
                  color="green-darken-3" 
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="form.ano"
                  label="Ano de Ingresso"
                  variant="outlined"
                  density="compact"
                  type="number"
                  placeholder="Ex: 2023"
                  color="green-darken-3"
                />
              </v-col>

              <v-col cols="12">
                <v-text-field
                  v-model="form.telefone"
                  label="Telefone (WhatsApp)"
                  :rules="[rules.required]"
                  variant="outlined"
                  density="compact"
                  placeholder="(99) 99999-9999"
                  @input="formatPhone"
                  color="green-darken-3" 
                />
              </v-col>
            </v-row>

            <v-card-actions class="justify-end mt-6 pa-0">
              <v-btn
                :loading="carregando"
                color="green-darken-3"
                size="large"
                variant="flat"
                type="submit"
                prepend-icon="mdi-content-save"
              >
                Salvar Alterações
              </v-btn>
            </v-card-actions>
          </v-form>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<style scoped>
.d-none {
  display: none;
}
</style>

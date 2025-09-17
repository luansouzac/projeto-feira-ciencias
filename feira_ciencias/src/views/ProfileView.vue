<template>
  <v-container class="py-10">
    <v-row justify="center">
      <v-col cols="12" md="6">
        <v-card class="pa-6 elevation-6 rounded-lg">
          <v-card-title class="text-h5 font-weight-bold mb-4">
            Meu Perfil
          </v-card-title>

          <v-row justify="center" class="mb-4">
            <v-avatar size="120" class="elevation-6">
              <v-img :src="user.photo || defaultPhoto" cover />
            </v-avatar>
          </v-row>

          <v-row justify="center" class="mb-2">
            <v-btn icon color="primary" @click="$refs.fileInput.click()">
              <v-icon>mdi-camera</v-icon>
            </v-btn>
            <input
              type="file"
              ref="fileInput"
              class="d-none"
              accept="image/*"
              @change="handleFileChange"
            />
          </v-row>

          <v-divider class="my-4" />

          <v-form ref="profileForm" lazy-validation>
            <v-row dense>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="user.nome"
                  label="Nome Completo"
                  :rules="[rules.required]"
                  outlined
                  dense
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="user.email"
                  label="Email"
                  :rules="[rules.required]"
                  outlined
                  dense
                />
              </v-col>

              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="user.cpf"
                  label="CPF"
                  :rules="[rules.required]"
                  outlined
                  dense
                  @input="formatCpf"
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="user.telefone"
                  label="Telefone"
                  :rules="[rules.required]"
                  outlined
                  dense
                  @input="formatPhone"
                />
              </v-col>

              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="user.instituicao"
                  label="Instituição"
                  :rules="[rules.required]"
                  outlined
                  dense
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="user.curso"
                  label="Curso"
                  :rules="[rules.required]"
                  outlined
                  dense
                />
              </v-col>

              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="user.id_matricula"
                  label="Matrícula"
                  :rules="[rules.required]"
                  outlined
                  dense
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-select
                  v-model="user.id_tipo_usuario"
                  :items="userTypes"
                  item-title="label"
                  item-value="value"
                  label="Tipo de Usuário"
                  :rules="[rules.required]"
                  outlined
                  dense
                />
              </v-col>
            </v-row>

            <v-card-actions class="justify-end mt-4">
              <v-btn color="primary" size="large" @click="saveProfile">
                Salvar perfil
              </v-btn>
            </v-card-actions>
          </v-form>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useNotificationStore } from '@/stores/notification';
import { useUsuarioStore } from '@/stores/usuarioStore';

const notificationStore = useNotificationStore();
const UsuarioStore = useUsuarioStore();

const defaultPhoto = 'https://via.placeholder.com/120x120.png?text=Foto';

const user = ref({
  nome: '',
  email: '',
  cpf: '',
  telefone: '',
  instituicao: '',
  curso: '',
  id_matricula: '',
  id_tipo_usuario: null,
  photo: null,
});

const userTypes = [
  { label: 'Administrador', value: 1 },
  { label: 'Professor', value: 2 },
  { label: 'Orientador', value: 3 },
  { label: 'Aluno', value: 4 },
];

const rules = {
  required: v => !!v || 'Campo obrigatório.',
};

const profileForm = ref(null);

const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = () => {
      user.value.photo = reader.result;
    };
    reader.readAsDataURL(file);
  }
};

const formatCpf = () => {
  let value = user.value.cpf.replace(/\D/g, '');
  if (value.length > 11) value = value.substring(0, 11);
  value = value.replace(/(\d{3})(\d)/, '$1.$2');
  value = value.replace(/(\d{3})(\d)/, '$1.$2');
  value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
  user.value.cpf = value;
};

const formatPhone = () => {
  let value = user.value.telefone.replace(/\D/g, '');
  if (value.length > 11) value = value.substring(0, 11);
  value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
  value = value.replace(/(\d{4,5})(\d{4})$/, '$1-$2');
  user.value.telefone = value;
};

const saveProfile = async () => {
  const requiredFields = [
    'nome', 'email', 'cpf', 'telefone', 'instituicao', 'curso', 'id_matricula', 'id_tipo_usuario',
  ];

  for (const field of requiredFields) {
    if (!user.value[field] || user.value[field].toString().trim() === '') {
      notificationStore.showError(`Campo ${field} é obrigatório.`);
      return;
    }
  }

  try {
    const userDataString = sessionStorage.getItem('user_data');
    if (!userDataString) {
      notificationStore.showError('Usuário não autenticado.');
      return;
    }

    const userData = JSON.parse(userDataString);
    const userId = userData.user.id_usuario;  // Ajustado para id_usuario
    const token = userData.token;

    await axios.put(`http://localhost:5174/api/usuarios/${userId}`, user.value, {
      headers: { Authorization: `Bearer ${token}` },
    });

    notificationStore.showSuccess('Perfil atualizado com sucesso!');
  } catch (error) {
    console.error('Erro ao salvar perfil:', error);
    notificationStore.showError('Erro ao salvar perfil.');
  }
};

onMounted(async () => {
  await UsuarioStore.fetchUsuarios();
  user.value = UsuarioStore.getUsuarioById(1);
});
</script>

<style scoped>
.d-none {
  display: none;
}
</style>

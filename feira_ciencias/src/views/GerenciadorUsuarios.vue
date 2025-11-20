<script setup>
import { ref, onMounted } from 'vue';
import api from '@/assets/plugins/axios.js';
import { useNotificationStore } from '@/stores/notification';

// --- Instâncias e Stores ---
const notificationStore = useNotificationStore();

// --- Estado da Página ---
const isModalOpen = ref(false);
const isLoading = ref(true)
const isSubmitting = ref(false);
const erro = ref(null);

const userTypes = ref([
    { id_tipo_usuario: 1, tipo: 'Administrador' },
    { id_tipo_usuario: 2, tipo: 'Aluno' },
    { id_tipo_usuario: 3, tipo: 'Orientador' },
    { id_tipo_usuario: 4, tipo: 'Avaliador' },
]); 
const usuarios = ref([]);

// Estado do Formulário
const form = ref(null);
const formData = ref({
    nome: '',
    email: '',
    id_matricula: '',
    senha_hash: '',
    id_tipo_usuario: null,
});
const passwordConfirm = ref('');

// --- Busca de Dados Iniciais (Apenas Usuários) ---
onMounted(async () => {
    try {
        const usersResponse = await api.get('/usuarios?limit=10'); 
        usuarios.value = usersResponse.data;
    } catch (err) {
        erro.value = "Falha ao carregar a lista inicial de usuários.";
        console.error(err);
    } finally {
        isLoading.value = false;
    }
});

// --- Funções do Formulário (restante do código...) ---
const resetForm = () => {
    formData.value = { nome: '', email: '', id_matricula: '', senha_hash: '', id_tipo_usuario: null };
    passwordConfirm.value = '';
    form.value?.resetValidation();
};

const rules = {
    required: v => !!v || 'Campo obrigatório',
    email: v => /.+@.+\..+/.test(v) || 'E-mail deve ser válido',
    min: v => v && v.length >= 6 || 'Mínimo de 6 caracteres',
    passMatch: v => v === formData.value.senha_hash || 'As senhas não coincidem',
};

const handleCreateUser = async () => {
    if (!form.value) return;
    const { valid } = await form.value.validate();
    if (!valid) return;

    isSubmitting.value = true;
    try {
        const payload = { ...formData.value };
        
        const response = await api.post('/usuarios', payload);
        
        usuarios.value.unshift(response.data);
        
        notificationStore.showSuccess(`Usuário ${response.data.nome} criado com sucesso!`);
        isModalOpen.value = false;
        resetForm();

    } catch (err) {
        let message = "Falha ao criar usuário.";
        if (err.response?.data?.errors?.email) {
            message = "E-mail já cadastrado.";
        } else if (err.response?.data?.errors?.id_matricula) {
            message = "Matrícula inválida ou já cadastrada.";
        }
        notificationStore.showError(message);
        console.error(err);
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <v-container>
        <h1 class="text-h4 font-weight-bold mb-4">Gestão de Usuários</h1>
        <p class="text-medium-emphasis mb-8">Crie novos usuários e atribua seus perfis de acesso (Tipo de Usuário).</p>

        <v-card variant="outlined" class="pa-4">
            <v-card-title class="d-flex align-center">
                <span class="text-h6">Administração de Contas</span>
                <v-spacer></v-spacer>
                <v-btn color="green-darken-3" prepend-icon="mdi-plus" @click="isModalOpen = true">
                    Novo Usuário
                </v-btn>
            </v-card-title>
            <v-divider class="my-4"></v-divider>
            
            <!-- Tabela de Usuários Existentes -->
            <div v-if="isLoading" class="text-center py-8">
                <v-progress-circular indeterminate color="grey-darken-1" size="32" />
            </div>
            <v-table v-else>
                <thead>
                    <tr>
                        <th class="text-left">Nome</th>
                        <th class="text-left d-none d-sm-table-cell">E-mail</th>
                        <th class="text-left">Perfil</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in usuarios" :key="user.id_usuario">
                        <td>{{ user.nome }}</td>
                        <td class="d-none d-sm-table-cell">{{ user.email }}</td>
                        <td>{{ user.tipo_usuario?.tipo || 'N/A' }}</td> 
                        <td class="text-right">
                           <v-btn icon="mdi-pencil" variant="text" size="small"></v-btn>
                           <v-btn icon="mdi-delete" color="red" variant="text" size="small"></v-btn>
                        </td>
                    </tr>
                </tbody>
            </v-table>
        </v-card>

        <!-- Modal de Criação de Usuário -->
        <v-dialog v-model="isModalOpen" persistent max-width="600px">
            <v-card>
                <v-card-title class="bg-green-darken-3 text-white">
                    <span class="text-h5">Cadastrar Novo Usuário</span>
                </v-card-title>
                <v-card-text class="pt-6">
                    <v-form ref="form" @submit.prevent="handleCreateUser">
                        <v-text-field v-model="formData.nome" label="Nome Completo" :rules="[rules.required]" variant="outlined" class="mb-4"></v-text-field>
                        
                        <v-text-field v-model="formData.email" label="E-mail" :rules="[rules.required, rules.email]" variant="outlined" class="mb-4"></v-text-field>
                        
                        <!-- ✅ ADICIONADO: Campo 'Matrícula' -->
                        <v-text-field v-model="formData.id_matricula" label="Matrícula" :rules="[rules.required]" variant="outlined" class="mb-4"></v-text-field>

                        <v-select
                            v-model="formData.id_tipo_usuario"
                            :items="userTypes"
                            item-title="tipo"
                            item-value="id_tipo_usuario"
                            label="Tipo de Usuário (Perfil)"
                            :rules="[rules.required]"
                            variant="outlined"
                            class="mb-4"
                        ></v-select>
                        
                        <v-text-field v-model="formData.senha_hash" label="Senha" type="password" :rules="[rules.required, rules.min]" variant="outlined" class="mb-4"></v-text-field>
                        
                        <v-text-field v-model="passwordConfirm" label="Confirmar Senha" type="password" :rules="[rules.required, rules.passMatch]" variant="outlined"></v-text-field>
                    </v-form>
                </v-card-text>
                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn color="grey-darken-1" variant="text" @click="isModalOpen = false; resetForm()">Cancelar</v-btn>
                    <v-btn color="green-darken-2" variant="flat" :loading="isSubmitting" @click="handleCreateUser">Criar Usuário</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>
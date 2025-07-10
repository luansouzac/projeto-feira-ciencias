<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

const router = useRouter()
const loading = ref(false)
const showPassword = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const form = ref({
    name: '',
    email: '',
    password: '',
    id_tipo_usuario: null,
})

const tiposUsuario = [
    { label: 'Administrador', value: 1 },
    { label: 'Aluno', value: 2 },
    { label: 'Avaliador', value: 3 },
]

const handleRegister = async () => {
    loading.value = true
    errorMessage.value = ''

    try {
        await axios.post('/register', {
            name: form.value.name,
            email: form.value.email,
            password: form.value.password,
            id_tipo_usuario: form.value.id_tipo_usuario,
        })

        successMessage.value = 'Usuário cadastrado com sucesso!'
        errorMessage.value = ''

        setTimeout(() => {
            router.push('/login')
        }, 1000)
    } catch (error) {
        if (error.response && error.response.data && error.response.data.message) {
            errorMessage.value = error.response.data.message
        } else {
            errorMessage.value = 'Erro ao registrar. Verifique os dados e tente novamente.'
        }
        successMessage.value = ''
    }
}
</script>

<template>
    <div class="d-flex flex-column fill-height">
        <v-sheet color="green-darken-4" class="d-flex flex-column justify-center align-center pa-6 text-white"
            style="flex: 0.4 1 0">
            <v-img
                src="https://www.ifsudestemg.edu.br/comunicacao-social/logos/if-sudeste-mg/logo-if-sudeste-mg-branco-vertical.png/@@images/image.png"
                max-height="100" contain class="mb-6" />
            <h1 class="text-h4 font-weight-bold text-center mb-2">Nome do Sistema</h1>
            <p class="text-h6 font-weight-light text-center">
                Plataforma de Gestão de Projetos do IF Sudeste MG
            </p>
        </v-sheet>

        <v-sheet class="d-flex flex-column justify-center align-center pa-8" style="flex: 0.6 1 0">
            <v-card elevation="0" class="w-100" max-width="500px">
                <h2 class="text-h4 font-weight-bold text-grey-darken-3 mb-2">Criar conta</h2>
                <p class="text-grey-darken-1 mb-8">
                    Preencha os campos abaixo para se cadastrar no sistema.
                </p>

                <v-form @submit.prevent="handleRegister">
                    <v-text-field v-model="form.name" label="Nome completo" variant="outlined"
                        prepend-inner-icon="mdi-account" required class="mb-4" />

                    <v-text-field v-model="form.email" label="E-mail" variant="outlined"
                        prepend-inner-icon="mdi-email-outline" required class="mb-4" />

                    <v-text-field v-model="form.password" label="Senha" variant="outlined"
                        prepend-inner-icon="mdi-lock-outline" :type="showPassword ? 'text' : 'password'"
                        :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                        @click:append-inner="showPassword = !showPassword" required class="mb-4" />

                    <v-select v-model="form.id_tipo_usuario" :items="tiposUsuario" item-title="label" item-value="value"
                        label="Tipo de Usuário" variant="outlined" prepend-inner-icon="mdi-account-cog-outline" required
                        class="mb-4" />

                    <v-alert v-if="errorMessage" type="error" variant="tonal" density="compact" class="mb-4"
                        :text="errorMessage" />
                    <v-alert v-if="successMessage" type="success" variant="tonal" density="compact" class="mb-4"
                        :text="successMessage" />

                    <v-btn :loading="loading" type="submit" color="green-darken-3" block size="large">
                        Registrar
                    </v-btn>

                    <div class="text-center mt-6">
                        <RouterLink to="/login" class="text-green-darken-3 text-decoration-none">
                            Já tem uma conta? Entrar
                        </RouterLink>
                    </div>
                </v-form>
            </v-card>
        </v-sheet>
    </div>
</template>

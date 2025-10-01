<script setup>
import { ref } from 'vue'
import api from '../assets/plugins/axios.js'
import { useRouter } from 'vue-router'

const router = useRouter()
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const form = ref({
    email: ''
})

const handleRegister = async () => {
    loading.value = true
    errorMessage.value = ''

    try {
        await api.post('/recuperar_senha', {
            email: form.value.email,
        })

        successMessage.value = 'Uma nova senha temporária foi enviada para o seu e-mail.'
        errorMessage.value = ''

        setTimeout(() => {
            router.push('/login')
        }, 1000)
    } catch (error) {
        if (error.response && error.response.data && error.response.data.message) {
            errorMessage.value = error.response.data.message
        } else {
            errorMessage.value = 'Erro ao criar a nova senha. Verifique os dados e tente novamente.'
        }
        successMessage.value = ''
        loading.value = false
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
                <h2 class="text-h4 font-weight-bold text-grey-darken-3 mb-2">Recuperar Senha</h2>
                <p class="text-grey-darken-1 mb-8">
                    Preencha o seu email para receber uma nova senha.
                </p>

                <v-form @submit.prevent="handleRegister">
                    <v-text-field v-model="form.email" label="E-mail" variant="outlined"
                        prepend-inner-icon="mdi-email-outline" required class="mb-4" />

                    <v-btn :loading="loading" type="submit" color="green-darken-3" block size="large">
                        Criar uma nova senha
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

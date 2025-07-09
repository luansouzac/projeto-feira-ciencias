<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios' 

const router = useRouter()

const form = ref({
  email: '',
  password: '',
})
const showPassword = ref(false)
const loading = ref(false)
const errorMessage = ref('')

const handleLogin = async () => { // Adicionamos 'async' para poder usar 'await'
  if (!form.value.email || !form.value.password) {
    errorMessage.value = 'Por favor, preencha todos os campos.'
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
   
    await axios.post('/login', form.value)

    console.log('Login bem-sucedido!')
    router.push({ name: 'about' }) 

  } catch (error) {
    console.error('Erro ao fazer login:', error)
    if (error.response && error.response.status === 401) {
      errorMessage.value = 'Credenciais inválidas. Verifique seu e-mail e senha.'
    } else {
      errorMessage.value = 'Ocorreu um erro no servidor. Tente novamente mais tarde.'
    }
  } finally {
    loading.value = false 
  }
}
</script>

<template>
  <div class="d-flex flex-column fill-height">
    <v-sheet
      color="green-darken-4"
      class="d-flex flex-column justify-center align-center pa-6 text-white"
      style="flex: 0.4 1 0;"
    >
      <v-img
        src="https://www.ifsudestemg.edu.br/comunicacao-social/logos/if-sudeste-mg/logo-if-sudeste-mg-branco-vertical.png/@@images/image.png"
        max-height="100"
        contain
        class="mb-6"
      />
      <h1 class="text-h4 font-weight-bold text-center mb-2">
        Nome do Sistema
      </h1>
      <p class="text-h6 font-weight-light text-center">
        Plataforma de Gestão de Projetos do IF Sudeste MG
      </p>
    </v-sheet>

    <v-sheet
      class="d-flex flex-column justify-center align-center pa-8"
      style="flex: 0.6 1 0;"
    >
      <v-card
        elevation="0"
        class="w-100"
        max-width="500px"
      >
        <h2 class="text-h4 font-weight-bold text-grey-darken-3 mb-2">
          Acessar o sistema
        </h2>
        <p class="text-grey-darken-1 mb-8">
          Utilize seu usuário e senha para continuar.
        </p>

        <v-form @submit.prevent="handleLogin">
          <v-text-field
            v-model="form.email"
            label="Usuário (E-mail)"
            variant="outlined"
            prepend-inner-icon="mdi-account-outline"
            required
            class="mb-4"
          />

          <v-text-field
            v-model="form.password"
            label="Senha"
            variant="outlined"
            prepend-inner-icon="mdi-lock-outline"
            :type="showPassword ? 'text' : 'password'"
            :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
            @click:append-inner="showPassword = !showPassword"
            required
            class="mb-4"
          />

          <v-alert
            v-if="errorMessage"
            type="error"
            variant="tonal"
            density="compact"
            class="mb-4"
            :text="errorMessage"
          />

          <v-btn
            :loading="loading"
            type="submit"
            color="green-darken-3"
            block
            size="large"
          >
            Acessar
          </v-btn>

          <div class="d-flex justify-space-between mt-6">
            <RouterLink to="/recuperar-senha" class="text-green-darken-3 text-decoration-none">Esqueceu sua senha?</RouterLink>
            <RouterLink to="/registrar" class="text-green-darken-3 text-decoration-none">Criar conta</RouterLink>
          </div>
        </v-form>
      </v-card>
    </v-sheet>
  </div>
</template>
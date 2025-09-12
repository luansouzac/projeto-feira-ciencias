<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../assets/plugins/axios.js'

const router = useRouter()

const form = ref({
  email: '',
  password: '',
})
const showPassword = ref(false)
const loading = ref(false)
const errorMessage = ref('')

const handleLogin = async () => { 
  if (!form.value.email || !form.value.password) {
    errorMessage.value = 'Por favor, preencha todos os campos.'
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    const { data } = await api.post('/login', form.value);

    const userDataToStore = {
        token: data.access_token,
        user: data.user
    };
    sessionStorage.setItem('user_data', JSON.stringify(userDataToStore));

    console.log('Login bem-sucedido!');
    
    router.push({ name: 'home' });

  } catch (error) {
    console.error('Erro retornado ao componente de login:', error)
    if (error.response && (error.response.status === 422 || error.response.status === 401)) {
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
  <div class="d-flex flex-column fill-height login-wrapper">
    <v-sheet
      color="green-darken-4"
      class="d-flex flex-column justify-center align-center pa-6 text-white login-header"
    >
      <v-img
        src="https://www.ifsudestemg.edu.br/comunicacao-social/logos/if-sudestemg/logo-if-sudeste-mg-branco-vertical.png/@@images/image.png"
        max-height="100"
        contain
        class="mb-6"
      />
      <h1 class="text-h4 font-weight-bold text-center mb-2 brand-title">
        Projetaí
      </h1>
      <p class="text-h6 font-weight-light text-center">
        Plataforma de gestão de projetos
      </p>
    </v-sheet>

    <div
      class="d-flex flex-column justify-center align-center pa-8 flex-grow-1 login-background"
    >
      <v-card
        elevation="0" 
        class="w-100 form-card pa-8" 
        max-width="500px"
      >
        <h2 class="text-h4 font-weight-bold text-center text-white mb-2">
          Acessar o sistema
        </h2>
        <p class="text-center text-white mb-8 text-body-1">
          Utilize seu usuário e senha para continuar.
        </p>

        <v-form @submit.prevent="handleLogin">
          <v-text-field
            v-model="form.email"
            label="Usuário (E-mail)"
            variant="outlined"
            prepend-inner-icon="mdi-account-outline"
            required
            class="mb-6 text-h6"
            density="comfortable" 
            color="white"  
            bg-color="rgba(255, 255, 255, 0.25)" 
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
            class="mb-6 text-h6"
            density="comfortable" 
            color="white" 
            bg-color="rgba(255, 255, 255, 0.25)" 
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
            class="mt-4"
          >
            Acessar
          </v-btn>

          <div class="d-flex justify-space-between mt-6">
            <RouterLink to="/recuperar-senha" class="text-white text-decoration-none text-body-2">Esqueceu sua senha?</RouterLink>
            <RouterLink to="/registrar" class="text-white text-decoration-none text-body-2">Criar conta</RouterLink>
          </div>
        </v-form>
      </v-card>
    </div>
  </div>
</template>

<style scoped>
.brand-title {
  font-family: 'Poppins', sans-serif;
  letter-spacing: 0.1em;
}

.login-wrapper {
  min-height: 100vh;
}

.login-background {
  position: relative;
  background-image: url('/bg-login.jpg');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  flex: 1;
}

.login-background::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.7);
  z-index: 0;
}

.login-header {
  flex: 0 0 auto;
}
    
.form-card {
  position: relative;
  z-index: 1;
  background-color: rgba(255, 255, 255, 0.15) !important;
  backdrop-filter: blur(10px);
  border-radius: 16px;
  box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
  border: 1px solid rgba(255, 255, 255, 0.18);
  /* Aumentando o padding vertical para dar mais espaço */
  padding-top: 32px !important;
  padding-bottom: 32px !important;
  height: 500px;
}

.v-field__input {
  color: #FFFFFF !important;
  font-size: 10px !important; /* Tamanho da fonte do texto digitado */
}

.v-label.v-field-label {
  color: #FFFFFF !important;
  opacity: 1 !important;
  font-size: 10px !important; /* Tamanho da fonte das labels */
}
</style>=
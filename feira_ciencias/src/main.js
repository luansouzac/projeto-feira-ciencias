import './assets/main.css'

import '@fontsource/poppins/400.css'; // Regular
import '@fontsource/poppins/700.css';

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import '@/assets/plugins/axios'
import vuetify from './assets/plugins/vuetify'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(vuetify)

app.mount('#app')

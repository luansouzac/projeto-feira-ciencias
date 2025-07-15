<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '../assets/plugins/axios.js' 
import { useNotificationStore } from '@/stores/notification'
import CrudModal from '@/components/CrudModal.vue';


const router = useRouter();
const notificationStore = useNotificationStore();

// --- ESTADO DA PÁGINA ---
const carregando = ref(true) 
const erro = ref(null)
const todosProjetos = ref([])
const filtroStatus = ref('Todos')
const nomeUsuario = ref('')
let userId = null;

// --- ESTADO PARA O MODAL ---
const isModalOpen = ref(false)
const isModalLoading = ref(false)
const currentItem = ref(null) // Guarda o item para edição (null para criação)

const userDataString = localStorage.getItem('user_data');
if (userDataString) {
  const userData = JSON.parse(userDataString);
  nomeUsuario.value = userData.user.nome;
  userId = userData.user.id_usuario; 
}

const modalConfig = {
  title: computed(() => (currentItem.value ? 'Editar Projeto' : 'Novo Projeto')),
  fields: [
    { 
      key: 'titulo', 
      label: 'Título do Projeto', 
      type: 'text',
      rules: [v => !!v || 'O título é obrigatório'],
    },
    { 
      key: 'problema', 
      label: 'Problema a ser Resolvido', 
      type: 'textarea',
      rules: [v => !!v || 'A descrição do problema é obrigatória'],
    },
    { 
      key: 'relevancia', 
      label: 'Relevância do Projeto', 
      type: 'textarea',
      rules: [v => !!v || 'A relevância é obrigatória'],
    },
    // { 
    //   key: 'id_evento', 
    //   label: 'Evento Associado', 
    //   type: 'select',
    //   options: [ { text: 'Feira de Ciências 2025', value: 1 }, { text: 'Mostra de Inovação', value: 2 } ],
    //   rules: [v => !!v || 'É necessário selecionar um evento'],
    // },
  ],
};

const opcoesStatus = [
  { title: 'Todos', value: 'Todos' },
  { title: 'Em Elaboração', value: 1 },
  { title: 'Aprovado', value: 2 },
  { title: 'Reprovado', value: 3 },
]
const statusMap = {
  1: { text: 'Em Elaboração', color: 'orange-darken-2' },
  2: { text: 'Aprovado', color: 'green-darken-2' },
  3: { text: 'Reprovado', color: 'red-darken-2' },
}

// métodos
onMounted(() => {
  if (!userId) {
    erro.value = "Usuário não encontrado. Por favor, faça o login novamente.";
    carregando.value = false;
    return;
  }
  api.get(`/usuarios/${userId}/projetos`)
    .then(response => {
      todosProjetos.value = response.data;
    })
    .catch(error => {
      console.error("Erro ao buscar projetos:", error);
      erro.value = "Não foi possível carregar os projetos.";
    })
    .finally(() => {
      carregando.value = false;
    });
})

const openCreateModal = () => {
  currentItem.value = null;
  isModalOpen.value = true;
};

const openEditModal = (projeto) => {
  currentItem.value = { ...projeto }; // Copia o objeto para evitar mutação direta
  isModalOpen.value = true;
};

const handleSave = async (formData) => {
  isModalLoading.value = true;
  
  if (!formData.id_projeto) {
      formData.id_responsavel = userId;
      formData.id_situacao = 1; 
      formData.id_evento = 1; //aqui trocar pelo id do evento quando tiver
  }

  try {
    if (formData.id_projeto) {
      const { data } = await api.put(`/projetos/${formData.id_projeto}`, formData);
      const index = todosProjetos.value.findIndex(p => p.id_projeto === data.id_projeto);
      if (index !== -1) todosProjetos.value[index] = data;
      notificationStore.showSuccess('Projeto alterado com sucesso!');
    } else {
      const { data } = await api.post('/projetos', formData);
      notificationStore.showSuccess('Projeto criado com sucesso!');
      todosProjetos.value.push(data);
    }
    isModalOpen.value = false; 
  } catch (error) {
    console.error("Erro ao salvar o projeto:", error);
  } finally {
    isModalLoading.value = false;
  }
};


const projetosFiltrados = computed(() => {
  if (filtroStatus.value === 'Todos' || !filtroStatus.value) {
    return todosProjetos.value
  }
  return todosProjetos.value.filter(p => p.id_situacao === filtroStatus.value)
})

const totalProjetos = computed(() => todosProjetos.value.length)
const projetosAprovados = computed(() => todosProjetos.value.filter(p => p.id_situacao === 2).length)

const goToProjectDetails = (id) => {
  router.push(`/projetos/${id}`)
}
</script>

<template>
  <v-container class="fill-height" fluid>
    <v-row align="center" justify="center">
      <v-col cols="12" md="8" lg="6" class="text-center">
        <h1 class="text-h4 font-weight-bold text-grey-darken-4">Bem-vindo de volta, {{ nomeUsuario }}!</h1>
        <p class="text-subtitle-1 text-grey-darken-1 mt-2">Aqui ficará as notificações do seu projeto</p>
      </v-col>
    </v-row>
  </v-container>
</template>

<style scoped>
.v-container {
  min-height: 80vh; 
}
</style>
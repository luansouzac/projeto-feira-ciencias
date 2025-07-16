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

const userDataString = sessionStorage.getItem('user_data');
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
  <v-container fluid>

    <v-row class="mb-8">
      <v-col cols="12" sm="6" md="4">
        <v-card color="green-darken-4" dark class="d-flex flex-column" height="100%">
          <v-card-text>
            <div class="d-flex align-center">
              <v-icon size="48" class="mr-4">mdi-plus-box-multiple</v-icon>
              <div>
                <div class="text-h5 font-weight-bold">Novo Projeto</div>
                <div class="text-subtitle-1">Inicie uma nova proposta</div>
              </div>
            </div>
          </v-card-text>
          <v-spacer></v-spacer>
          <v-card-actions>
            <v-btn variant="outlined" block @click="openCreateModal">Criar agora</v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="4">
        <v-card variant="tonal" color="grey-darken-1" class="d-flex flex-column" height="100%">
          <v-card-text>
            <div class="d-flex align-center">
              <v-icon size="48" class="mr-4">mdi-folder-account-outline</v-icon>
              <div>
                <div class="text-h4 font-weight-bold text-grey-darken-4">{{ totalProjetos }}</div>
                <div class="text-subtitle-2 text-grey-darken-2">Projetos Registrados</div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="4">
        <v-card variant="tonal" color="green-darken-2" class="d-flex flex-column" height="100%">
          <v-card-text>
            <div class="d-flex align-center">
              <v-icon size="48" class="mr-4">mdi-check-decagram-outline</v-icon>
              <div>
                <div class="text-h4 font-weight-bold text-green-darken-4">{{ projetosAprovados }}</div>
                <div class="text-subtitle-2 text-green-darken-3">Projetos Aprovados</div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-divider class="my-6"></v-divider>
    <v-row align="center" class="mb-4">
      <v-col cols="12" md="6">
        <h2 class="text-h5 font-weight-bold text-grey-darken-4">Meus Projetos</h2>
        <p class="text-subtitle-2 text-grey-darken-1">Gerencie e acompanhe o andamento de suas propostas.</p>
      </v-col>
      <v-col cols="12" md="6" class="d-flex justify-md-end">
        <v-select v-model="filtroStatus" :items="opcoesStatus" label="Filtrar por Status" variant="outlined" density="compact" hide-details clearable style="max-width: 280px;"></v-select>
      </v-col>
    </v-row>
    
    <v-row v-if="carregando">
      <v-col v-for="n in 3" :key="n" cols="12" sm="6" lg="4">
        <v-skeleton-loader type="card"></v-skeleton-loader>
      </v-col>
    </v-row>

    <v-row v-else-if="projetosFiltrados.length === 0">
      <v-col cols="12">
        <v-card flat border class="text-center pa-8">
          <v-icon size="60" class="mb-4 text-grey-lighten-1">mdi-folder-search-outline</v-icon>
          <p class="text-grey-darken-1">Nenhum projeto encontrado.</p>
        </v-card>
      </v-col>
    </v-row>

    <v-row v-else>
      <v-col v-for="projeto in projetosFiltrados" :key="projeto.id_projeto" cols="12" sm="6" lg="4">
        <v-card class="d-flex flex-column" height="100%" hover variant="outlined">
          <v-card-item>
            <div class="d-flex justify-space-between align-center mb-2">
              <v-card-title class="text-wrap py-0">{{ projeto.titulo }}</v-card-title>
              <v-chip :color="statusMap[projeto.id_situacao]?.color || 'grey'" size="small" label>{{ statusMap[projeto.id_situacao]?.text || 'Desconhecido' }}</v-chip>
            </div>
          </v-card-item>
          <v-card-text class="py-2">
            <p class="text-body-2 text-grey-darken-2">{{ projeto.problema }}</p>
          </v-card-text>
          <v-spacer></v-spacer>
          <v-divider></v-divider>
          <v-card-actions>
            <v-btn color="green-darken-3" variant="tonal" @click="goToProjectDetails(projeto.id_projeto)">Ver Detalhes</v-btn>
            <v-spacer></v-spacer>
            <v-btn icon="mdi-pencil" variant="text" size="small" @click="openEditModal(projeto)"></v-btn>
            <v-btn icon="mdi-delete" variant="text" color="grey" size="small"></v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>

    <CrudModal
      v-model="isModalOpen"
      :title="modalConfig.title"
      :fields="modalConfig.fields"
      :item="currentItem"
      :loading="isModalLoading"
      @save="handleSave"
    />
  </v-container>
</template>

<style scoped>
.v-card-title.text-wrap {
  white-space: normal;
  line-height: 1.3em;
  font-weight: 500;
}
.v-card-text {
  min-height: 60px;
}
</style>

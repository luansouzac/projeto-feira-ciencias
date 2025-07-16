<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '../assets/plugins/axios.js' 
import { useNotificationStore } from '@/stores/notification'
import CrudModal from '@/components/CrudModal.vue';

//Adicionando o store do evento
import { useEventoStore } from '@/stores/eventoStore'


const router = useRouter();
const notificationStore = useNotificationStore();
const eventoStore = useEventoStore();


  const modalConfig = {
    title: computed(() => (currentItem.value ? 'Editar Projeto' : 'Novo Projeto')),
    fields: [
      { 
        key: 'nome', 
        label: 'Nome do evento', 
        type: 'text',
        rules: [v => !!v || 'O nome do evento é obrigatório'],
      },
      { 
        key: 'ativo', 
        label: 'Ativo', 
        type: 'checkbox',
        rules: '',
        value: 1,
      },
    ],
  };

  const deleteEvento = async (evento) => {
    if (confirm(`Tem certeza que deseja deletar o evento ${evento.nome}?`)) {
      const success = await eventoStore.deleteEvento(evento.id_evento);
      if (success) {
        notificationStore.showSuccess('Evento deletado com sucesso!');
      } else {
        alert('Falha ao deletar evento: ' + eventoStore.getEventErrors);
      }
    }
  };
  
  // --- ESTADO DA PÁGINA ---
  const filtroAtivo = ref('Todos')
  
  // --- ESTADO PARA O MODAL ---
  const isModalOpen = ref(false)
  const isModalLoading = ref(false)
  const currentItem = ref(null) // Guarda o item para edição (null para criação)
  
  const eventosFiltrados = computed(() => {
    if (filtroAtivo.value === 'Todos' || !filtroAtivo.value) {
      return eventoStore.getAllEventos;
    }
    return eventoStore.getAllEventos;
    //return eventoStore.getAllEventos.filter(p => p.ativo === 1);
  })
  
  // métodos
  const openCreateModal = () => {
    currentItem.value = null;
    isModalOpen.value = true;
  };
  
  const openEditModal = (evento) => {
    currentItem.value = { ...evento }; // Copia o objeto para evitar mutação direta
    isModalOpen.value = true;
  };
  
  const handleSave = async (formData) => {
    isModalLoading.value = true;
    
    try {
      if (formData.id_evento) {
        if( eventoStore.updateEvento(formData.id_evento, formData) )
        notificationStore.showSuccess('Projeto alterado com sucesso!');
      else
      console.error("Erro ao salvar o projeto:", error);    
  } else {
    if( eventoStore.createEvento(formData) )
    notificationStore.showSuccess('Projeto criado com sucesso!');
  else
  console.error("Erro ao salvar o projeto:", error);
}
isModalOpen.value = false; 
} catch (error) {
  console.error("Erro ao salvar o projeto:", error);
} finally {
  isModalLoading.value = false;
}
};

const opcoesAtivo = [
  { title: 'Todos', value: 'Todos' },
  { title: 'Ativo', value: 1 },
  { title: 'Inativo', value: 0 },
]


const totalEventos = useEventoStore.getTotal;

// Carregar eventos ao montar o componente
onMounted(() => {
  eventoStore.fetchEventos();
});


</script>

<template>
  <v-container fluid>

    <v-row class="mb-8">
      <v-spacer></v-spacer> <v-col cols="auto">
        <v-btn color="green-darken-4" class="mt-6" @click="openCreateModal">Novo Evento</v-btn>
      </v-col>
    </v-row>

    <v-divider class="my-6"></v-divider>
    <v-row align="center" class="mb-4">
      <v-col cols="12" md="6">
        <h2 class="text-h5 font-weight-bold text-grey-darken-4">Eventos</h2>
        <p class="text-subtitle-2 text-grey-darken-1">Gerencie e acompanhe os eventos cadastrados.</p>
      </v-col>
      <v-col cols="12" md="6" class="d-flex justify-md-end">
        <v-select v-model="filtroAtivo" :items="opcoesAtivo" label="Filtrar por Ativo" variant="outlined" density="compact" hide-details clearable style="max-width: 280px;"></v-select>
      </v-col>
    </v-row>
    
    <v-row>
        <v-card-text>
            <div v-if="eventoStore.isLoadingEventos" class="text-center py-5">
              <v-progress-circular indeterminate color="primary"></v-progress-circular>
              <p class="mt-2">Carregando eventos...</p>
            </div>
            <div v-else-if="eventoStore.getEventoErrors" class="text-center py-5 text-red-darken-2">
              <v-icon icon="mdi-alert-circle"></v-icon>
              <p class="mt-2">Erro ao carregar eventos: {{ eventoStore.getEventoErrors }}</p>
            </div>
            <div v-else-if="eventosFiltrados.length === 0" class="text-center py-5">
              <p>Nenhum evento encontrado com os filtros aplicados.</p>
            </div>
            <v-list v-else>
              <v-list-item v-for="evento in eventosFiltrados" :key="evento.id_evento" class="mb-2 border rounded">
                <v-list-item-content>
                  <v-list-item-title class="font-weight-bold">
                    {{ evento.nome }}
                  </v-list-item-title>
                  <v-list-item-subtitle>
                    <v-chip :color="evento.ativo==1 ? 'green' : 'red'" density="compact">
                      {{ evento.ativo ? 'Ativo' : 'Inativo' }}
                    </v-chip>
                  </v-list-item-subtitle>
                </v-list-item-content>
                <template v-slot:append>
                  <v-btn
                    icon="mdi-pencil"
                    variant="text"
                    size="small"
                    @click="openEditModal(evento)"
                  ></v-btn>
                  <v-btn
                    icon="mdi-delete"
                    variant="text"
                    size="small"
                    color="grey"
                    @click="deleteEvento(evento)"
                  ></v-btn>
                </template>
              </v-list-item>
            </v-list>
          </v-card-text>
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

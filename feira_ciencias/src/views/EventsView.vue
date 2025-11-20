<script setup>
import { ref, onMounted, computed } from 'vue'
import { useNotificationStore } from '@/stores/notification'
import CrudModal from '@/components/CrudModal.vue';
import { useEventoStore } from '@/stores/eventoStore'
import EventoVinculoModal from '@/components/modals/EventoVinculoModal.vue';
import api from '@/assets/plugins/axios.js';

//Adicionando o store do evento
import { storeToRefs } from 'pinia';

const notificationStore = useNotificationStore();
const eventoStore = useEventoStore();
const { eventos, isLoadingEventos, getEventErrors, getAllEventos } = storeToRefs(eventoStore);


// --- ESTADO PARA O MODAL DE EVENTO ---
const isModalOpen = ref(false)
const isModalLoading = ref(false)
const currentItem = ref(null)

// ✅ ESTADO PARA O MODAL DE VÍNCULO
const isLinkModalOpen = ref(false);
const eventoParaVincular = ref(null);

// --- ESTADO DA PÁGINA ---
const filtroAtivo = ref('Todos')

const modalConfig = {
  title: computed(() => (currentItem.value ? 'Editar Evento' : 'Novo Evento')),
  fields: [
    {
      key: 'nome',
      label: 'Nome do Evento',
      type: 'text',
      cols: 12,
      rules: [v => !!v || 'O nome é obrigatório'],
      defaultValue: '',
    },
    {
      key: 'data_evento',
      label: 'Data do Evento',
      type: 'date',
      cols: 12,
      rules: [v => !!v || 'A data é obrigatória'],
      defaultValue: null,
    },
    {
      key: 'inicio_submissao',
      label: 'Início da Submissão',
      type: 'datetime-local',
      cols: 12, md: 6,
      rules: [v => !!v || 'O início é obrigatório'],
      defaultValue: '',
    },
    {
      key: 'fim_submissao',
      label: 'Fim da Submissão',
      type: 'datetime-local',
      cols: 12, md: 6,
      rules: [
        v => !!v || 'O fim é obrigatório',
        (v, form) => new Date(v) > new Date(form.inicio_submissao) || 'Deve ser após o início.',
      ],
      defaultValue: '',
    },
    {
      key: 'inicio_inscricao',
      label: 'Início da Inscrição',
      type: 'date',
      cols: 12, md: 6,
      rules: [v => !!v || 'O início é obrigatório'],
      defaultValue: '',
    },
    {
      key: 'fim_inscricao',
      label: 'Fim da Inscrição',
      type: 'date',
      cols: 12, md: 6,
      defaultValue: '',
    },
    {
      key: 'min_pessoas',
      label: 'Mínimo de Pessoas',
      type: 'number',
      cols: 12, md: 6,
      rules: [v => v > 0 || 'Deve ser maior que zero'],
      defaultValue: 1,
    },
    {
      key: 'max_pessoas',
      label: 'Máximo de Pessoas',
      type: 'number',
      cols: 12, md: 6,
      rules: [
        v => v > 0 || 'Deve ser maior que zero',
        (v, form) => v >= form.min_pessoas || 'Deve ser maior ou igual ao mínimo.',
      ],
      defaultValue: 5,
    },
    {
      key: 'ativo',
      label: 'Ativo',
      type: 'checkbox',
      cols: 12,
      defaultValue: 1,
    },
  ],
};

const deleteEvento = async (evento) => {
  if (confirm(`Tem certeza que deseja deletar o evento ${evento.nome}?`)) {
    const success = await eventoStore.deleteEvento(evento.id_evento);
    if (success) {
      notificationStore.showSuccess('Evento deletado com sucesso!');
    } else {
      notificationStore.showError('Falha ao deletar evento: ' + eventoStore.getEventErrors);
    }
  }
};

// --- COMPUTEDS E MÉTODOS DE AÇÃO ---
const eventosFiltrados = computed(() => {
  if (filtroAtivo.value === 'Todos' || !filtroAtivo.value) {
    return getAllEventos.value;
  }
  return getAllEventos.value.filter(p => p.ativo == filtroAtivo.value);
})

// métodos
const openCreateModal = () => {
  currentItem.value = null;
  isModalOpen.value = true;
};

const openEditModal = (evento) => {
  currentItem.value = { ...evento };
  isModalOpen.value = true;
};

// ✅ NOVO MÉTODO PARA ABRIR O MODAL DE VÍNCULO
const openLinkModal = (evento) => {
  eventoParaVincular.value = evento;
  isLinkModalOpen.value = true;
}

const handleSave = async (formData) => {
  isModalLoading.value = true;

  try {
    const isEditing = currentItem.value && currentItem.value.id_evento;
    let success = false;
    let successMessage = '';
    let savedEvent = null;

    if (isEditing) {
      success = await eventoStore.updateEvento(currentItem.value.id_evento, formData);
      // Busca a versão atualizada do objeto na store (necessário para o modal de vínculo)
      savedEvent = getAllEventos.value.find(e => e.id_evento === currentItem.value.id_evento);
      successMessage = 'Evento alterado com sucesso!';
    } else {
      // O store deve retornar o novo objeto criado para o usarmos no modal de vínculo
      savedEvent = await eventoStore.createEvento(formData);
      success = !!savedEvent;
      successMessage = 'Evento criado com sucesso! Por favor, vincule os Orientadores.';
    }

    if (success) {
      notificationStore.showSuccess(successMessage);
      isModalOpen.value = false;
      currentItem.value = null;
    } else {
      notificationStore.showError('Falha ao salvar o evento: ' + getEventErrors.value);
    }

  } catch (error) {
    console.error("Erro crítico ao salvar o evento:", error);
    notificationStore.showError('Ocorreu um erro inesperado. Tente novamente.');
  } finally {
    isModalLoading.value = false;
  }
};

const opcoesAtivo = [
  { title: 'Todos', value: 'Todos' },
  { title: 'Ativo', value: '1' },
  { title: 'Inativo', value: '0' },
]


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
        <v-select v-model="filtroAtivo" :items="opcoesAtivo" label="Filtrar por Ativo" variant="outlined"
          density="compact" hide-details clearable style="max-width: 280px;"></v-select>
      </v-col>
    </v-row>

    <v-row>
      <v-card-text>
        <div v-if="isLoadingEventos" class="text-center py-5">
          <v-progress-circular indeterminate color="primary"></v-progress-circular>
          <p class="mt-2">Carregando eventos...</p>
        </div>
        <div v-else-if="getEventErrors" class="text-center py-5 text-red-darken-2">
          <v-icon icon="mdi-alert-circle"></v-icon>
          <p class="mt-2">Erro ao carregar eventos: {{ getEventErrors }}</p>
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
                <v-chip :color="evento.ativo == 1 ? 'green' : 'red'" density="compact">
                  {{ evento.ativo ? 'Ativo' : 'Inativo' }}
                </v-chip>
              </v-list-item-subtitle>
            </v-list-item-content>
            <template v-slot:append>
              <v-btn variant="text" size="small" color="green" @click.stop="openLinkModal(evento)">
                <v-icon>mdi-link</v-icon>

                <v-tooltip activator="parent">Vincular Orientadores/Avaliadores</v-tooltip>
              </v-btn>
              <v-btn icon="mdi-pencil" variant="text" size="small" @click.stop="openEditModal(evento)"></v-btn>
              <v-btn icon="mdi-delete" variant="text" size="small" color="grey"
                @click.stop="deleteEvento(evento)"></v-btn>
            </template>
          </v-list-item>
        </v-list>
      </v-card-text>
    </v-row>

    <!-- Modal de Criação/Edição de Evento (Existente) -->
    <CrudModal v-model="isModalOpen" :title="modalConfig.title" :fields="modalConfig.fields" :item="currentItem"
      :loading="isModalLoading" @save="handleSave" />

    <!-- ✅ NOVO MODAL DE VÍNCULO (Adicionado no template) -->
    <EventoVinculoModal v-model="isLinkModalOpen" :evento="eventoParaVincular" />
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
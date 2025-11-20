<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import api from '@/assets/plugins/axios.js';
import { useNotificationStore } from '@/stores/notification';

const props = defineProps({
  modelValue: Boolean,
  evento: Object, // Recebe o objeto Evento completo
});

const emit = defineEmits(['update:modelValue']);
const notificationStore = useNotificationStore();

// --- ESTADO LOCAL ---
const activeTab = ref('orientadores');
const isSearching = ref(false);
const usersList = ref([]); // Lista de usuários para o seletor (Orientadores e Avaliadores)
const currentLinks = ref({ orientadores: [], avaliadores: [] });
const isLoadingLinks = ref(true);

// --- ESTADO DO FORMULÁRIO DE VÍNCULO ---
const userToLink = ref(null);
const isSubmitting = ref(false);

// --- PROPRIEDADES COMPUTADAS ---
const eventoId = computed(() => props.evento?.id_evento);

// Filtra a lista principal de usuários, removendo aqueles que já estão vinculados
const availableUsers = computed(() => {
    const isOrientadorTab = activeTab.value === 'orientadores';
    const usersInCurrentTab = isOrientadorTab ? currentLinks.value.orientadores : currentLinks.value.avaliadores;
    const linkedUserIds = usersInCurrentTab.map(link => link.id_usuario || link.id_orientador || link.id_avaliador);
    
    // Filtra para remover os usuários que já têm um vínculo
    return usersList.value.filter(user => !linkedUserIds.includes(user.id_usuario));
});

// --- LÓGICA DE BUSCA ---

const fetchUsersForLinking = async () => {
    isSearching.value = true;
    try {
        // Busca usuários que podem ser orientadores (4) ou avaliadores (3)
        const response = await api.get('/usuarios?id_tipo_usuario_in=3,4');
        usersList.value = response.data;
    } catch (err) {
        notificationStore.showError('Falha ao carregar lista de professores/avaliadores.');
    } finally {
        isSearching.value = false;
    }
};

const fetchCurrentLinks = async () => {
    // A verificação do eventoId agora é feita pelo watcher (Passo 3)
    isLoadingLinks.value = true;
    try {
        const [orientadoresResponse, avaliadoresResponse] = await Promise.all([
            api.get(`/eventos/${eventoId.value}/orientadores`),
            api.get(`/eventos/${eventoId.value}/avaliadores`),
        ]);
        currentLinks.value.orientadores = orientadoresResponse.data;
        currentLinks.value.avaliadores = avaliadoresResponse.data;
    } catch (err) {
        notificationStore.showError('Falha ao carregar vínculos existentes.');
    } finally {
        isLoadingLinks.value = false;
    }
};

// --- MÉTODOS DE VÍNCULO ---

const linkUser = async () => {
    if (!userToLink.value) return notificationStore.showWarning('Selecione um usuário para vincular.');
    if (!eventoId.value) return notificationStore.showError('ID do evento não disponível.');

    isSubmitting.value = true;
    const tipo = activeTab.value;

    try {
        const payload = { id_usuario: userToLink.value };
        const response = await api.post(`/eventos/${eventoId.value}/${tipo}`, payload);

        const newLink = response.data;
        // Tenta encontrar o usuário na lista para atualizar a UI
        newLink.usuario = usersList.value.find(u => u.id_usuario === newLink.id_orientador || u.id_usuario === newLink.id_avaliador);
        
        currentLinks.value[tipo].push(newLink);
        
        notificationStore.showSuccess(`Vínculo de ${tipo} criado com sucesso!`);
        userToLink.value = null;

    } catch (err) {
        notificationStore.showError(err.response?.data?.erro || 'Não foi possível criar o vínculo.');
    } finally {
        isSubmitting.value = false;
    }
};

const unlinkUser = async (link) => {
    if (!confirm('Tem certeza que deseja remover este vínculo?')) return;
    if (!eventoId.value) return notificationStore.showError('ID do evento não disponível.');

    const tipo = activeTab.value;
    const usuarioId = link.id_usuario || link.id_orientador || link.id_avaliador;
    
    try {
        await api.delete(`/eventos/${eventoId.value}/${tipo}/${usuarioId}`);
        
        currentLinks.value[tipo] = currentLinks.value[tipo].filter(l => (l.id_orientador || l.id_avaliador) !== usuarioId);
        
        notificationStore.showSuccess('Vínculo removido com sucesso!');

    } catch (err) {
        notificationStore.showError('Falha ao remover vínculo.');
    }
};

// --- WATCHERS E INICIALIZAÇÃO ---

// 1. Carrega a lista de usuários assim que o componente é montado
onMounted(fetchUsersForLinking);

// 2. ✅ WATCHER PRINCIPAL CORRIGIDO: Reage à mudança do ID e à abertura do modal
watch(
    () => [props.modelValue, eventoId.value], 
    ([isOpening, newId]) => {
        // Se o modal estiver aberto E tiver um ID válido, busca os vínculos
        if (isOpening && newId) {
            fetchCurrentLinks();
        } else if (!isOpening) {
            // Limpa o estado ao fechar
            currentLinks.value = { orientadores: [], avaliadores: [] };
            userToLink.value = null;
            isLoadingLinks.value = true;
        }
    }
);


const close = () => emit('update:modelValue', false);
</script>

<template>
  <v-dialog :model-value="modelValue" @update:modelValue="close" persistent max-width="800px">
    <v-card>
      <v-card-title class="bg-green-darken-3 text-white">
        <v-icon start>mdi-link-variant</v-icon>
        <span class="text-h5">Gerenciar Vínculos: {{ evento?.nome }}</span>
      </v-card-title>
      <v-card-text>
        <v-tabs v-model="activeTab" color="green-darken-3" class="mb-4">
          <v-tab value="orientadores">Orientadores ({{ currentLinks.orientadores.length }})</v-tab>
          <v-tab value="avaliadores">Avaliadores ({{ currentLinks.avaliadores.length }})</v-tab>
        </v-tabs>

        <v-row>
            <v-col cols="12" md="6" class="pr-md-6">
                <h3 class="text-h6 font-weight-medium mb-3">Adicionar {{ activeTab === 'orientadores' ? 'Orientador' : 'Avaliador' }}</h3>
                <v-select
                    v-model="userToLink"
                    :items="availableUsers"
                    item-title="nome"
                    item-value="id_usuario"
                    :label="`Buscar por ${activeTab === 'orientadores' ? 'Professor/Orientador' : 'Avaliador'}`"
                    variant="outlined"
                    :loading="isSearching"
                    hide-details
                    class="mb-3"
                    clearable
                ></v-select>
                <v-btn
                    color="green-darken-3"
                    :disabled="!userToLink"
                    :loading="isSubmitting"
                    @click="linkUser"
                    block
                >
                    Vincular ao Evento
                </v-btn>
            </v-col>

            <v-col cols="12" md="6" class="pl-md-6 border-l">
                <h3 class="text-h6 font-weight-medium mb-3">Vinculados Atualmente</h3>
                <v-progress-linear v-if="isLoadingLinks" indeterminate color="grey" />
                <v-list v-else dense>
                    <div v-if="currentLinks[activeTab].length === 0" class="text-center text-grey py-4">
                        Nenhum(a) {{ activeTab === 'orientadores' ? 'orientador(a)' : 'avaliador(a)' }} vinculado(a).
                    </div>
                    <v-list-item v-for="link in currentLinks[activeTab]" :key="link.id">
                        <v-list-item-title class="font-weight-medium">{{ link.avaliador?.nome || link.orientador?.nome || 'Usuário Desconhecido' }}</v-list-item-title>
                        <v-list-item-subtitle>{{ link.usuario?.email || link.orientador?.email || 'N/A' }}</v-list-item-subtitle>
                        <template v-slot:append>
                            <v-btn icon="mdi-close" variant="text" size="small" color="red-lighten-1" @click="unlinkUser(link)"></v-btn>
                        </template>
                    </v-list-item>
                </v-list>
            </v-col>
        </v-row>
      </v-card-text>
      <v-card-actions class="pa-4">
        <v-spacer></v-spacer>
        <v-btn color="grey-darken-1" variant="text" @click="close">Fechar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
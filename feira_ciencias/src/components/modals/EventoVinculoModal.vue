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
const currentLinks = ref({ orientadores: [], avaliadores: [] });
const isLoadingLinks = ref(true);

// --- ESTADO PARA BUSCA DINÂMICA ---
const userToLink = ref(null);
const usersSearchResults = ref([]); // ✅ Resultados da busca dinâmica
const isSearchingUsers = ref(false); // ✅ Novo estado para feedback de loading
const isSubmitting = ref(false);

// --- PROPRIEDADES COMPUTADAS ---
const eventoId = computed(() => props.evento?.id_evento);

// Define os IDs de perfil que podem ser vinculados (baseado no seu EventoVinculoController)
const availableRoles = computed(() => {
    return [3, 4]; // Orientador e Avaliador
});

// Filtra os usuários buscados, removendo aqueles que já estão vinculados
const availableUsers = computed(() => {
    const isOrientadorTab = activeTab.value === 'orientadores';
    const usersInCurrentTab = isOrientadorTab ? currentLinks.value.orientadores : currentLinks.value.avaliadores;
    const linkedUserIds = usersInCurrentTab.map(link => link.id_usuario || link.id_orientador || link.id_avaliador);
    
    // Filtra os resultados da busca dinâmica para remover os já vinculados
    return usersSearchResults.value.filter(user => !linkedUserIds.includes(user.id_usuario));
});

// --- LÓGICA DE BUSCA DA API ---

const fetchCurrentLinks = async () => {
    if (!eventoId.value) {
        isLoadingLinks.value = false;
        return;
    }
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

// ✅ FUNÇÃO CRUCIAL: Chama o backend para buscar usuários enquanto o administrador digita
const fetchUsers = async (search) => {
    // Só busca se a query tiver 2 caracteres ou mais para economizar recursos
    if (search && search.length > 1) {
        isSearchingUsers.value = true;
        try {
            const rolesString = availableRoles.value.join(',');
            // Chama o endpoint do UsuarioController com os filtros de tipo e a pesquisa
            const response = await api.get(`/usuarios?id_tipo_usuario_in=3,4&search=${search}`);
            usersSearchResults.value = response.data;
        } catch (err) {
            notificationStore.showError('Falha na busca de usuários.');
        } finally {
            isSearchingUsers.value = false;
        }
    } else {
        usersSearchResults.value = []; // Limpa os resultados se a pesquisa for muito curta
    }
};

// --- MÉTODOS DE VÍNCULO (Link/Unlink) ---

const linkUser = async () => {
    if (!userToLink.value) return notificationStore.showWarning('Selecione um usuário para vincular.');
    if (!eventoId.value) return notificationStore.showError('ID do evento não disponível.');

    isSubmitting.value = true;
    const tipo = activeTab.value;

    try {
        const payload = { id_usuario: userToLink.value };
        const response = await api.post(`/eventos/${eventoId.value}/${tipo}`, payload);

        const newLink = response.data;
        // Pega os dados completos do usuário recém-vinculado para exibir o nome
        const userData = usersSearchResults.value.find(u => u.id_usuario === userToLink.value);
        
        newLink[tipo === 'orientadores' ? 'orientador' : 'avaliador'] = userData;
        
        currentLinks.value[tipo].push(newLink);
        
        notificationStore.showSuccess(`Vínculo de ${tipo} criado com sucesso!`);
        userToLink.value = null;
        usersSearchResults.value = []; // Limpa os resultados após o sucesso

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

// Reage à mudança do ID e à abertura do modal para buscar os vínculos
watch(
    () => [props.modelValue, eventoId.value], 
    ([isOpening, newId]) => {
        if (isOpening && newId) {
            fetchCurrentLinks();
            // NUNCA chamar fetchUsersForLinking aqui! A busca é dinâmica.
        } else if (!isOpening) {
            // Limpa o estado ao fechar
            currentLinks.value = { orientadores: [], avaliadores: [] };
            usersSearchResults.value = [];
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
                
                <!-- ✅ CAMPO CORRIGIDO: v-autocomplete para busca dinâmica -->
                <v-autocomplete
                    v-model="userToLink"
                    :items="availableUsers"
                    item-title="nome"
                    item-value="id_usuario"
                    :label="`Buscar por ${activeTab === 'orientadores' ? 'Professor/Orientador' : 'Avaliador'} (min. 2 caracteres)`"
                    variant="outlined"
                    :loading="isSearchingUsers"
                    hide-details
                    class="mb-3"
                    clearable
                    
                    @update:search="fetchUsers"
                    no-data-text="Digite um nome ou e-mail para buscar."
                >
                    <!-- Template para exibir o nome e o e-mail na lista de resultados -->
                    <template v-slot:item="{ props, item }">
                        <v-list-item v-bind="props" :title="item.raw.nome" :subtitle="item.raw.email"></v-list-item>
                    </template>
                </v-autocomplete>
                
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
                        <!-- Usamos o relacionamento correto para exibir o e-mail -->
                        <v-list-item-subtitle>{{ link.avaliador?.email || link.orientador?.email || 'N/A' }}</v-list-item-subtitle>
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
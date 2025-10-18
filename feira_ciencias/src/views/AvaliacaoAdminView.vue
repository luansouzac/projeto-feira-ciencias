    <script setup>
    import { ref, onMounted, computed, watch } from 'vue';
    import { useNotificationStore } from '@/stores/notification';
    import api from '@/assets/plugins/axios.js';

    // --- Stores e Estado ---
    const notificationStore = useNotificationStore();
    const loading = ref(true);
    const erro = ref(null);
    const activeTab = ref('atribuicoes');

    // --- Dados da Página ---
    const projetos = ref([]);
    const avaliadoresDisponiveis = ref([]); // Todos os usuários que podem ser avaliadores
    const atribuicoes = ref([]); // Avaliadores já atribuídos ao projeto selecionado
    const selectedProjectId = ref(null);
    const selectedAvaliadorId = ref(null);
    const isSubmitting = ref(false);

    // --- Busca de Dados Iniciais ---
    onMounted(async () => {
      try {
        const [projetosResponse, avaliadoresResponse] = await Promise.all([
          api.get('/projetos'), // Busca todos os projetos
          api.get('/usuarios?id_tipo_usuario_in=3,4') // Busca usuários do tipo 3 (Avaliador) e 4 (Orientador)
        ]);
        projetos.value = projetosResponse.data;
        avaliadoresDisponiveis.value = avaliadoresResponse.data;
      } catch (err) {
        erro.value = "Não foi possível carregar os dados iniciais.";
        console.error(err);
      } finally {
        loading.value = false;
      }
    });

    // --- Lógica de Atribuição ---
    // Observa quando o utilizador seleciona um projeto na lista
    watch(selectedProjectId, async (newProjectId) => {
      if (!newProjectId) {
        atribuicoes.value = [];
        return;
      }
      try {
        const response = await api.get(`/projetos/${newProjectId}/avaliadores`);
        atribuicoes.value = response.data;
      } catch (err) {
        notificationStore.showError("Falha ao buscar os avaliadores deste projeto.");
      }
    });

    const atribuirAvaliador = async () => {
      if (!selectedProjectId.value || !selectedAvaliadorId.value) {
        notificationStore.showWarning("Selecione um projeto e um avaliador.");
        return;
      }
      isSubmitting.value = true;
      try {
        const payload = {
          id_projeto: selectedProjectId.value,
          id_avaliador: selectedAvaliadorId.value,
        };
        const response = await api.post('/avaliador-projeto', payload);
        
        // Atualiza a lista na interface com os dados do avaliador
        const avaliadorInfo = avaliadoresDisponiveis.value.find(a => a.id_usuario === response.data.id_avaliador);
        atribuicoes.value.push({ ...response.data, avaliador: avaliadorInfo });
        
        selectedAvaliadorId.value = null; // Limpa o seletor
        notificationStore.showSuccess("Avaliador atribuído com sucesso!");
      } catch (err) {
        notificationStore.showError(err.response?.data?.erro || "Não foi possível atribuir o avaliador.");
      } finally {
        isSubmitting.value = false;
      }
    };

    const removerAtribuicao = async (atribuicao) => {
      if (!confirm(`Tem certeza que deseja remover ${atribuicao.avaliador.nome} deste projeto?`)) return;

      try {
        await api.delete(`/avaliador-projeto/${atribuicao.id}`);
        atribuicoes.value = atribuicoes.value.filter(a => a.id !== atribuicao.id);
        notificationStore.showSuccess("Avaliador desassociado com sucesso.");
      } catch (err) {
        notificationStore.showError(err.response?.data?.erro || "Não foi possível remover a atribuição.");
      }
    };

    // Filtra a lista de avaliadores para não mostrar quem já foi atribuído
    const avaliadoresParaAtribuir = computed(() => {
      const idsAtribuidos = atribuicoes.value.map(a => a.id_avaliador);
      return avaliadoresDisponiveis.value.filter(a => !idsAtribuidos.includes(a.id_usuario));
    });
    </script>

    <template>
      <v-container>
        <div v-if="loading" class="text-center py-16">
          <v-progress-circular indeterminate color="green-darken-3" size="64" />
        </div>
        <v-alert v-else-if="erro" type="error" variant="tonal" prominent>{{ erro }}</v-alert>

        <div v-else>
          <h1 class="text-h4 font-weight-bold mb-2">Painel de Gestão de Avaliações</h1>
          <p class="text-medium-emphasis mb-8">Atribua avaliadores aos projetos e gira os questionários.</p>

          <v-card>
            <v-tabs v-model="activeTab" bg-color="green-darken-4" color="white">
              <v-tab value="atribuicoes">Atribuição de Avaliadores</v-tab>
              <v-tab value="questionarios">Gestão de Questionários</v-tab>
            </v-tabs>

            <v-window v-model="activeTab">
              <!-- Aba de Atribuições -->
              <v-window-item value="atribuicoes">
                <v-card-text class="pa-6">
                  <v-select
                    v-model="selectedProjectId"
                    :items="projetos"
                    item-title="titulo"
                    item-value="id_projeto"
                    label="Selecione um Projeto para Gerir"
                    variant="outlined"
                    class="mb-6"
                    clearable
                  ></v-select>

                  <div v-if="selectedProjectId">
                    <h2 class="text-h6 font-weight-medium mb-4">Avaliadores Atribuídos ({{ atribuicoes.length }}/3)</h2>
                    <v-list v-if="atribuicoes.length > 0" lines="one" border rounded class="mb-8">
                      <v-list-item v-for="atribuicao in atribuicoes" :key="atribuicao.id" :title="atribuicao.avaliador.nome">
                        <template v-slot:prepend>
                          <v-avatar color="grey-lighten-2">
                            <v-icon>mdi-account-tie-outline</v-icon>
                          </v-avatar>
                        </template>
                        <template v-slot:append>
                          <v-btn icon="mdi-close" variant="text" color="grey" @click="removerAtribuicao(atribuicao)"></v-btn>
                        </template>
                      </v-list-item>
                    </v-list>
                    <p v-else class="text-center text-grey py-4">Nenhum avaliador atribuído a este projeto ainda.</p>

                    <!-- Formulário para adicionar novo avaliador -->
                    <div v-if="atribuicoes.length < 3">
                      <h2 class="text-h6 font-weight-medium mb-4">Adicionar Novo Avaliador</h2>
                      <v-row align="center">
                        <v-col cols="12" md="8">
                          <v-select
                            v-model="selectedAvaliadorId"
                            :items="avaliadoresParaAtribuir"
                            item-title="nome"
                            item-value="id_usuario"
                            label="Selecione um Avaliador"
                            variant="outlined"
                            hide-details
                          ></v-select>
                        </v-col>
                        <v-col cols="12" md="4">
                          <v-btn 
                            color="green-darken-3" 
                            @click="atribuirAvaliador"
                            :loading="isSubmitting"
                            block 
                            size="large"
                          >
                            Atribuir
                          </v-btn>
                        </v-col>
                      </v-row>
                    </div>
                     <v-alert v-else type="success" variant="tonal" class="mt-6">
                        Este projeto já atingiu o número máximo de 3 avaliadores.
                    </v-alert>

                  </div>
                </v-card-text>
              </v-window-item>

              <!-- Aba de Questionários -->
              <v-window-item value="questionarios">
                <v-card-text class="pa-6 text-center text-grey">
                  <v-icon size="48" class="mb-4">mdi-text-box-search-outline</v-icon>
                  <p>A funcionalidade de gestão de questionários será implementada aqui.</p>
                </v-card-text>
              </v-window-item>
            </v-window>
          </v-card>
        </div>
      </v-container>
    </template>
    
    <style scoped>
    .v-window-item {
      padding: 0 !important;
    }
    </style>
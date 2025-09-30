<script setup>
import { ref, computed, watch, defineProps, defineEmits } from 'vue'
import api from '@/assets/plugins/axios.js'

const props = defineProps({
  modelValue: { type: Boolean, default: false }, // Para v-model
  membrosAtuais: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue', 'add-member'])

// --- Estado interno do modal ---
const searchQuery = ref('')
const searchResults = ref([])
const isSearching = ref(false)
const searchError = ref(null)
let searchTimeout = null

// Filtra os resultados da busca para não mostrar quem já é membro
const filteredSearchResults = computed(() => {
  if (!searchResults.value.length) return []
  const memberIds = props.membrosAtuais.map((m) => m.id_usuario)
  return searchResults.value.filter((user) => !memberIds.includes(user.id_usuario))
})

// Função de busca com debounce
watch(searchQuery, (newQuery) => {
  clearTimeout(searchTimeout)
  searchResults.value = []
  searchError.value = null

  if (!newQuery || newQuery.length < 3) {
    isSearching.value = false
    return
  }

  isSearching.value = true
  searchTimeout = setTimeout(async () => {
    try {
      const response = await api.get(`/usuarios?search=${newQuery}`)
      searchResults.value = response.data.data || response.data
    } catch (err) {
      console.error('Erro ao buscar usuários:', err)
      searchError.value = 'Não foi possível buscar usuários.'
    } finally {
      isSearching.value = false
    }
  }, 500)
})

// Emite o evento para o pai adicionar o membro
const selectUserToAdd = (user) => {
  emit('add-member', user)
}

// Reseta o estado ao fechar
const close = () => {
  searchQuery.value = ''
  searchResults.value = []
  searchError.value = null
  isSearching.value = false
  clearTimeout(searchTimeout)
  emit('update:modelValue', false)
}
</script>

<template>
  <v-dialog :model-value="modelValue" @update:model-value="close" persistent max-width="600px">
    <v-card>
      <v-card-title class="d-flex align-center text-h5 bg-green-darken-3 text-white">
        <v-icon start>mdi-account-search-outline</v-icon>
        Adicionar Membro à Equipe
        <v-spacer></v-spacer>
        <v-btn icon="mdi-close" variant="text" @click="close"></v-btn>
      </v-card-title>
      <v-card-text class="pt-6">
        <v-text-field
          v-model="searchQuery"
          label="Buscar por nome, matrícula ou e-mail"
          placeholder="Digite pelo menos 3 caracteres"
          variant="outlined"
          prepend-inner-icon="mdi-magnify"
          autofocus
          clearable
        ></v-text-field>
        <div class="mt-4" style="min-height: 200px">
          <div v-if="isSearching" class="text-center py-8">
            <v-progress-circular indeterminate color="green-darken-2"></v-progress-circular>
            <p class="mt-3 text-grey-darken-1">Buscando usuários...</p>
          </div>
          <v-alert v-else-if="searchError" type="error" variant="tonal">{{ searchError }}</v-alert>
          <div v-else-if="filteredSearchResults.length === 0 && searchQuery.length >= 3" class="text-center py-8 text-grey-darken-1">
            <v-icon size="48" class="mb-4">mdi-account-off-outline</v-icon>
            <p>Nenhum usuário encontrado ou todos já são membros.</p>
          </div>
          <v-list v-else-if="filteredSearchResults.length > 0">
            <v-list-item
              v-for="user in filteredSearchResults"
              :key="user.id_usuario"
              :title="user.nome"
              :subtitle="user.email"
            >
              <template v-slot:append>
                <v-btn color="green" variant="outlined" size="small" @click="selectUserToAdd(user)">
                  Adicionar
                </v-btn>
              </template>
            </v-list-item>
          </v-list>
        </div>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>
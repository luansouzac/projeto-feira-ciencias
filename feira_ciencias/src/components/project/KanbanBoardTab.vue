<script setup>
import { defineProps, defineEmits } from 'vue'

const props = defineProps({
  tasks: { type: Array, required: true },
  membros: { type: Array, required: true },
  canCreateTasks: { type: Boolean, default: false },
})

const emit = defineEmits([
  'open-create-task',
  'open-edit-task',
  'open-delete-task',
  'open-feedback-task',
  'open-submit-task',
  'update-task-status',
])

const kanbanColumns = [
  { title: 'A Fazer', status: 1, color: 'grey' },
  { title: 'Em Andamento', status: 2, color: 'blue' },
  { title: 'Concluído', status: 3, color: 'green' },
]

const filterTasksByStatus = (status) => {
  return props.tasks.filter((task) => task.id_situacao === status)
}

const getMemberName = (userId) => {
  if (!userId || !props.membros || props.membros.length === 0) return 'Não atribuído'
  const membro = props.membros.find((m) => m.id_usuario === userId)
  return membro ? membro.usuario.nome : 'Não atribuído'
}

const formatDateSimple = (dateString) => {
  if (!dateString) return null
  const date = new Date(dateString)
  const userTimezoneOffset = date.getTimezoneOffset() * 60000
  return new Date(date.getTime() - userTimezoneOffset).toISOString().split('T')[0]
}

const handleDragStart = (event, task) => {
  event.dataTransfer.setData('text/plain', task.id_tarefa)
  event.dataTransfer.dropEffect = 'move'
}

const handleDrop = (event, newStatus) => {
  if (!props.canCreateTasks) return
  const taskId = event.dataTransfer.getData('text/plain')
  const task = props.tasks.find((t) => t.id_tarefa == taskId)
  if (task && task.id_situacao !== newStatus) {
    // Emite o evento para o pai, que fará a chamada da API
    emit('update-task-status', { taskId, newStatus, task })
  }
}
</script>

<template>
  <v-card-title class="d-flex justify-space-between align-center">
    <span>Quadro de Tarefas</span>
    <v-btn
      v-if="canCreateTasks"
      color="green"
      variant="flat"
      @click="emit('open-create-task')"
      prepend-icon="mdi-plus"
    >
      Nova Tarefa
    </v-btn>
  </v-card-title>
  <v-card-text class="bg-grey-lighten-4">
    <v-row>
      <v-col
        v-for="column in kanbanColumns"
        :key="column.status"
        cols="12"
        md="4"
        @dragover.prevent
        @drop="handleDrop($event, column.status)"
      >
        <div class="pa-4 rounded-lg fill-height" :class="`bg-${column.color}-lighten-5`">
          <div class="d-flex align-center mb-4">
            <v-icon :color="column.color" class="mr-2">mdi-circle-medium</v-icon>
            <span class="font-weight-bold text-grey-darken-3">{{ column.title }}</span>
            <v-chip size="small" :color="column.color" class="ml-2">
              {{ filterTasksByStatus(column.status).length }}
            </v-chip>
          </div>
          <v-card
            v-for="task in filterTasksByStatus(column.status)"
            :key="task.id_tarefa"
            class="mb-3 task-card"
            theme="light"
            variant="flat"
            :class="{ 'not-draggable': !canCreateTasks }"
            :draggable="canCreateTasks"
            @dragstart="handleDragStart($event, task)"
          >
            <v-card-text class="font-weight-medium text-grey-darken-4 pb-1">
              {{ task.descricao }}
              </v-card-text>
            <v-card-actions class="pt-0 px-4 pb-2">
              <v-chip v-if="task.id_usuario_atribuido" size="small" color="green" variant="tonal" label>
                <v-icon start icon="mdi-account-outline"></v-icon>
                {{ getMemberName(task.id_usuario_atribuido) }}
              </v-chip>
              <v-spacer></v-spacer>
              <template v-if="canCreateTasks">
                <v-btn icon="mdi-pencil" variant="text" size="x-small" @click.stop="emit('open-edit-task', task)"></v-btn>
                <v-btn icon="mdi-delete-outline" color="red" variant="text" size="x-small" @click.stop="emit('open-delete-task', task)"></v-btn>
              </template>
              <v-btn color="primary" variant="text" size="small" @click.stop="emit('open-feedback-task', task)">Feedbacks</v-btn>
              <v-btn v-if="task.id_situacao !== 3" color="green" variant="text" size="small" @click.stop="emit('open-submit-task', task)">Entregar</v-btn>
            </v-card-actions>
          </v-card>
        </div>
      </v-col>
    </v-row>
  </v-card-text>
</template>
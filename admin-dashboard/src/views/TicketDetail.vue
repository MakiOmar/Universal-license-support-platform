<template>
  <div>
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>

    <div v-else-if="ticket" class="space-y-6">
      <div class="bg-white shadow rounded-lg p-6">
        <div class="mb-4">
          <h3 class="text-lg font-semibold">{{ ticket.subject }}</h3>
          <p class="text-sm text-gray-500">Ticket #{{ ticket.ticket_number }}</p>
        </div>
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <span class="text-sm font-medium text-gray-500">Status: </span>
            <span :class="getStatusClass(ticket.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
              {{ ticket.status }}
            </span>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-500">Priority: </span>
            <span :class="getPriorityClass(ticket.priority)" class="px-2 py-1 text-xs font-semibold rounded-full">
              {{ ticket.priority }}
            </span>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-500">Customer: </span>
            <span class="text-sm">{{ ticket.customer?.email || 'N/A' }}</span>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-500">Created: </span>
            <span class="text-sm">{{ formatDate(ticket.created_at) }}</span>
          </div>
        </div>
        <div class="border-t pt-4">
          <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ ticket.description }}</p>
        </div>
      </div>

      <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-semibold mb-4">Replies</h3>
        <div v-if="replies.length === 0" class="text-gray-500 text-sm">No replies yet</div>
        <div v-else class="space-y-4">
          <div v-for="reply in replies" :key="reply.id" class="border-l-4 border-indigo-500 pl-4">
            <div class="flex justify-between mb-2">
              <span class="text-sm font-medium">{{ reply.user_type }}</span>
              <span class="text-sm text-gray-500">{{ formatDate(reply.created_at) }}</span>
            </div>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ reply.message }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../services/api'

const route = useRoute()
const ticket = ref<any>(null)
const replies = ref([])
const loading = ref(false)

function getStatusClass(status: string) {
  const classes: Record<string, string> = {
    open: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-yellow-100 text-yellow-800',
    resolved: 'bg-green-100 text-green-800',
    closed: 'bg-gray-100 text-gray-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function getPriorityClass(priority: string) {
  const classes: Record<string, string> = {
    low: 'bg-gray-100 text-gray-800',
    medium: 'bg-yellow-100 text-yellow-800',
    high: 'bg-orange-100 text-orange-800',
    urgent: 'bg-red-100 text-red-800'
  }
  return classes[priority] || 'bg-gray-100 text-gray-800'
}

function formatDate(date: string | null) {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString()
}

async function fetchTicket() {
  loading.value = true
  try {
    const response = await api.get(`/tickets/${route.params.id}`)
    ticket.value = response.data.data || response.data
    replies.value = ticket.value.replies || []
  } catch (error) {
    console.error('Failed to fetch ticket:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchTicket()
})
</script>


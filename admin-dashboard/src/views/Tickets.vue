<template>
  <div>
    <div class="mb-4 flex justify-between items-center">
      <div class="flex gap-4">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search tickets..."
          class="px-4 py-2 border rounded-md"
        />
        <select v-model="statusFilter" class="px-4 py-2 border rounded-md">
          <option value="">All Status</option>
          <option value="open">Open</option>
          <option value="in_progress">In Progress</option>
          <option value="resolved">Resolved</option>
          <option value="closed">Closed</option>
        </select>
        <select v-model="priorityFilter" class="px-4 py-2 border rounded-md">
          <option value="">All Priorities</option>
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
          <option value="urgent">Urgent</option>
        </select>
      </div>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>

    <div v-else class="bg-white shadow rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ticket #</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="ticket in filteredTickets" :key="ticket.id">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono">{{ ticket.ticket_number }}</td>
            <td class="px-6 py-4 text-sm">{{ ticket.subject }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ ticket.customer?.email || 'N/A' }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getPriorityClass(ticket.priority)" class="px-2 py-1 text-xs font-semibold rounded-full">
                {{ ticket.priority }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getStatusClass(ticket.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                {{ ticket.status }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ formatDate(ticket.created_at) }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
              <router-link :to="`/tickets/${ticket.id}`" class="text-indigo-600 hover:text-indigo-900">View</router-link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api, { ADMIN_API_BASE_URL } from '../services/api'

const tickets = ref([])
const loading = ref(false)
const searchQuery = ref('')
const statusFilter = ref('')
const priorityFilter = ref('')

const filteredTickets = computed(() => {
  let filtered = tickets.value

  if (statusFilter.value) {
    filtered = filtered.filter((t: any) => t.status === statusFilter.value)
  }

  if (priorityFilter.value) {
    filtered = filtered.filter((t: any) => t.priority === priorityFilter.value)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter((t: any) =>
      t.ticket_number?.toLowerCase().includes(query) ||
      t.subject?.toLowerCase().includes(query) ||
      t.customer?.email?.toLowerCase().includes(query)
    )
  }

  return filtered
})

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
  return new Date(date).toLocaleDateString()
}

async function fetchTickets() {
  loading.value = true
  try {
    const response = await api.get(`${ADMIN_API_BASE_URL}/tickets`, { params: { per_page: 100 } })
    tickets.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch tickets:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchTickets()
})
</script>


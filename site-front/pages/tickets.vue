<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Support Tickets</h1>
        <p class="mt-2 text-sm text-gray-600">Manage your support requests</p>
      </div>
      <button
        @click="showCreateModal = true"
        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
      >
        New Ticket
      </button>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex gap-4">
      <select v-model="statusFilter" @change="fetchTickets" class="px-4 py-2 border rounded-md">
        <option value="">All Status</option>
        <option value="open">Open</option>
        <option value="in_progress">In Progress</option>
        <option value="waiting_customer">Waiting Customer</option>
        <option value="resolved">Resolved</option>
        <option value="closed">Closed</option>
      </select>
      <select v-model="priorityFilter" @change="fetchTickets" class="px-4 py-2 border rounded-md">
        <option value="">All Priority</option>
        <option value="low">Low</option>
        <option value="medium">Medium</option>
        <option value="high">High</option>
        <option value="urgent">Urgent</option>
      </select>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>

    <div v-else-if="tickets.length === 0" class="bg-white shadow rounded-lg p-8 text-center">
      <p class="text-gray-500">No tickets found.</p>
    </div>

    <div v-else class="bg-white shadow rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ticket #</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="ticket in tickets" :key="ticket.id">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono">{{ ticket.ticket_number }}</td>
            <td class="px-6 py-4 text-sm text-gray-900">{{ ticket.subject }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ticket.product?.name || 'N/A' }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="{
                  'bg-gray-100 text-gray-800': ticket.priority === 'low',
                  'bg-yellow-100 text-yellow-800': ticket.priority === 'medium',
                  'bg-orange-100 text-orange-800': ticket.priority === 'high',
                  'bg-red-100 text-red-800': ticket.priority === 'urgent'
                }"
                class="px-2 py-1 text-xs font-semibold rounded-full"
              >
                {{ ticket.priority }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="{
                  'bg-blue-100 text-blue-800': ticket.status === 'open',
                  'bg-yellow-100 text-yellow-800': ticket.status === 'in_progress',
                  'bg-purple-100 text-purple-800': ticket.status === 'waiting_customer',
                  'bg-green-100 text-green-800': ticket.status === 'resolved',
                  'bg-gray-100 text-gray-800': ticket.status === 'closed'
                }"
                class="px-2 py-1 text-xs font-semibold rounded-full"
              >
                {{ ticket.status }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(ticket.created_at) }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
              <NuxtLink
                :to="`/tickets/${ticket.id}`"
                class="text-indigo-600 hover:text-indigo-900"
              >
                View
              </NuxtLink>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create Ticket Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click.self="closeCreateModal"
    >
      <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Ticket</h3>
        <form @submit.prevent="handleCreateTicket" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
            <input
              v-model="ticketForm.subject"
              type="text"
              required
              maxlength="255"
              class="w-full px-3 py-2 border rounded-md"
              placeholder="Brief description of your issue"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
            <textarea
              v-model="ticketForm.description"
              required
              rows="5"
              maxlength="5000"
              class="w-full px-3 py-2 border rounded-md"
              placeholder="Please provide detailed information about your issue..."
            ></textarea>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
              <select v-model="ticketForm.priority" class="w-full px-3 py-2 border rounded-md">
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
              <select v-model="ticketForm.category" class="w-full px-3 py-2 border rounded-md">
                <option value="">Select category</option>
                <option value="technical">Technical</option>
                <option value="billing">Billing</option>
                <option value="feature_request">Feature Request</option>
                <option value="bug_report">Bug Report</option>
                <option value="account">Account</option>
                <option value="license">License</option>
              </select>
            </div>
          </div>

          <div v-if="ticketError" class="text-sm text-red-600">
            {{ ticketError }}
          </div>

          <div class="flex justify-end gap-2">
            <button
              type="button"
              @click="closeCreateModal"
              class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="creating"
              class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
            >
              <span v-if="creating">Creating...</span>
              <span v-else>Create Ticket</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth'
})

const api = useApi()
const { CUSTOMER_API_BASE_URL } = api
const { toastSuccess, toastError } = useAlerts()

const loading = ref(true)
const tickets = ref<any[]>([])
const statusFilter = ref('')
const priorityFilter = ref('')

const showCreateModal = ref(false)
const creating = ref(false)
const ticketError = ref('')
const ticketForm = ref({
  subject: '',
  description: '',
  priority: 'medium',
  category: '',
  license_id: null as number | null,
  product_id: null as number | null,
})

onMounted(async () => {
  await fetchTickets()
})

function formatDate(date: string | null) {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}

function closeCreateModal() {
  if (creating.value) return
  showCreateModal.value = false
  ticketForm.value = {
    subject: '',
    description: '',
    priority: 'medium',
    category: '',
    license_id: null,
    product_id: null,
  }
  ticketError.value = ''
}

async function fetchTickets() {
  loading.value = true
  try {
    const params: any = {}
    if (statusFilter.value) params.status = statusFilter.value
    if (priorityFilter.value) params.priority = priorityFilter.value
    
    const queryString = new URLSearchParams(params).toString()
    const url = `${CUSTOMER_API_BASE_URL}/tickets${queryString ? `?${queryString}` : ''}`
    
    const response = await api.get<{ data: any[] }>(url)
    tickets.value = response.data || []
  } catch (error) {
    console.error('Failed to fetch tickets:', error)
    toastError('Failed to load tickets')
  } finally {
    loading.value = false
  }
}

async function handleCreateTicket() {
  creating.value = true
  ticketError.value = ''

  try {
    const payload: any = {
      subject: ticketForm.value.subject,
      description: ticketForm.value.description,
      priority: ticketForm.value.priority,
    }
    
    if (ticketForm.value.category) {
      payload.category = ticketForm.value.category
    }
    if (ticketForm.value.license_id) {
      payload.license_id = ticketForm.value.license_id
    }
    if (ticketForm.value.product_id) {
      payload.product_id = ticketForm.value.product_id
    }

    await api.post(`${CUSTOMER_API_BASE_URL}/tickets`, payload)
    toastSuccess('Ticket created successfully')
    closeCreateModal()
    await fetchTickets()
  } catch (err: any) {
    ticketError.value = err.message || 'Failed to create ticket. Please try again.'
    toastError(ticketError.value)
  } finally {
    creating.value = false
  }
}
</script>


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
          <option value="waiting_customer">Waiting Customer</option>
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
      <button
        @click="openCreateModal"
        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
      >
        New Ticket
      </button>
    </div>

    <!-- Create Ticket Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click.self="closeCreateModal"
    >
      <div class="relative top-10 mx-auto p-5 border w-full max-w-3xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Ticket</h3>
          <form @submit.prevent="handleCreate" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Customer *</label>
                <select
                  v-model.number="form.customer_id"
                  required
                  class="w-full px-3 py-2 border rounded-md"
                >
                  <option value="">Select customer</option>
                  <option
                    v-for="customer in customers"
                    :key="customer.id"
                    :value="customer.id"
                  >
                    {{ customer.email }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
                <select
                  v-model.number="form.product_id"
                  class="w-full px-3 py-2 border rounded-md"
                >
                  <option value="">None</option>
                  <option
                    v-for="product in products"
                    :key="product.id"
                    :value="product.id"
                  >
                    {{ product.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">License</label>
                <select
                  v-model.number="form.license_id"
                  class="w-full px-3 py-2 border rounded-md"
                >
                  <option value="">None</option>
                  <option
                    v-for="license in licenses"
                    :key="license.id"
                    :value="license.id"
                  >
                    {{ license.license_key }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                <select v-model="form.priority" class="w-full px-3 py-2 border rounded-md">
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select v-model="form.category" class="w-full px-3 py-2 border rounded-md">
                  <option value="">None</option>
                  <option value="technical">Technical</option>
                  <option value="billing">Billing</option>
                  <option value="feature_request">Feature Request</option>
                  <option value="bug_report">Bug Report</option>
                  <option value="account">Account</option>
                  <option value="license">License</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
              <input
                v-model="form.subject"
                type="text"
                required
                class="w-full px-3 py-2 border rounded-md"
                placeholder="Short summary of the issue"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
              <textarea
                v-model="form.description"
                rows="5"
                required
                class="w-full px-3 py-2 border rounded-md"
                placeholder="Describe the issue in detail..."
              ></textarea>
            </div>

            <div v-if="error" class="text-sm text-red-600">
              {{ error }}
            </div>

            <div class="flex justify-end gap-2 pt-2">
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
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assigned To</th>
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
            <td class="px-6 py-4 whitespace-nowrap text-sm">
              <span v-if="ticket.assigned_admin">{{ ticket.assigned_admin.name }}</span>
              <span v-else class="text-gray-400">Unassigned</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ formatDate(ticket.created_at) }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm space-x-3">
              <router-link :to="`/tickets/${ticket.id}`" class="text-indigo-600 hover:text-indigo-900">View</router-link>
              <select
                :value="ticket.assigned_to || ''"
                @change="assignTicket(ticket, $event.target.value ? parseInt($event.target.value) : null)"
                class="text-xs border rounded px-2 py-1"
              >
                <option value="">Assign...</option>
                <option
                  v-for="admin in admins"
                  :key="admin.id"
                  :value="admin.id"
                >
                  {{ admin.name }}
                </option>
              </select>
              <button
                type="button"
                @click="quickUpdateStatus(ticket, 'in_progress')"
                v-if="ticket.status === 'open'"
                class="text-gray-700 hover:text-gray-900 text-xs"
              >
                Mark In Progress
              </button>
              <button
                type="button"
                @click="quickUpdateStatus(ticket, 'resolved')"
                v-if="ticket.status === 'open' || ticket.status === 'in_progress'"
                class="text-green-700 hover:text-green-900 text-xs"
              >
                Mark Resolved
              </button>
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
import { useAlerts } from '../utils/alerts'

const tickets = ref([])
const loading = ref(false)
const searchQuery = ref('')
const statusFilter = ref('')
const priorityFilter = ref('')

const showCreateModal = ref(false)
const creating = ref(false)
const error = ref('')

const customers = ref<any[]>([])
const products = ref<any[]>([])
const licenses = ref<any[]>([])
const admins = ref<any[]>([])

const form = ref({
  customer_id: '' as number | '',
  product_id: '' as number | '',
  license_id: '' as number | '',
  subject: '',
  description: '',
  priority: 'medium',
  category: ''
})

const { toastSuccess, toastError } = useAlerts()

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

async function fetchMetadata() {
  try {
    const [customersRes, productsRes, licensesRes, adminsRes] = await Promise.all([
      api.get(`${ADMIN_API_BASE_URL}/customers`, { params: { per_page: 100 } }),
      api.get(`${ADMIN_API_BASE_URL}/products`, { params: { per_page: 100 } }),
      api.get(`${ADMIN_API_BASE_URL}/licenses`, { params: { per_page: 100 } }),
      api.get(`${ADMIN_API_BASE_URL}/admins`)
    ])

    customers.value = customersRes.data.data || customersRes.data
    products.value = productsRes.data.data || productsRes.data
    licenses.value = licensesRes.data.data || licensesRes.data
    admins.value = adminsRes.data || []
  } catch (e) {
    console.error('Failed to fetch ticket metadata:', e)
  }
}

async function assignTicket(ticket: any, adminId: number | null) {
  const originalAssignedTo = ticket.assigned_to
  ticket.assigned_to = adminId

  try {
    const response = await api.post(`${ADMIN_API_BASE_URL}/tickets/${ticket.id}/assign`, {
      assigned_to: adminId || null
    })
    const updated = response.data.data || response.data
    tickets.value = tickets.value.map((t: any) => (t.id === updated.id ? updated : t))
    toastSuccess(adminId ? 'Ticket assigned successfully' : 'Ticket unassigned successfully')
  } catch (err) {
    console.error('Failed to assign ticket:', err)
    ticket.assigned_to = originalAssignedTo
    toastError('Failed to assign ticket. Please try again.')
  }
}

function openCreateModal() {
  form.value = {
    customer_id: '' as number | '',
    product_id: '' as number | '',
    license_id: '' as number | '',
    subject: '',
    description: '',
    priority: 'medium',
    category: ''
  }
  error.value = ''
  showCreateModal.value = true
}

function closeCreateModal() {
  if (creating.value) {
    return
  }
  showCreateModal.value = false
}

async function handleCreate() {
  creating.value = true
  error.value = ''

  const payload: any = {
    customer_id: form.value.customer_id,
    subject: form.value.subject,
    description: form.value.description
  }

  if (form.value.product_id) {
    payload.product_id = form.value.product_id
  }
  if (form.value.license_id) {
    payload.license_id = form.value.license_id
  }
  if (form.value.priority) {
    payload.priority = form.value.priority
  }
  if (form.value.category) {
    payload.category = form.value.category
  }

  try {
    const response = await api.post(`${ADMIN_API_BASE_URL}/tickets`, payload)
    const created = response.data.data || response.data
    tickets.value.unshift(created)
    showCreateModal.value = false
    toastSuccess('Ticket created successfully')
  } catch (err: any) {
    if (err.response?.data?.message) {
      error.value = err.response.data.message
    } else if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      error.value = Object.values(errors).flat().join(', ')
    } else {
      error.value = 'Failed to create ticket. Please try again.'
    }
    toastError(error.value || 'Failed to create ticket. Please try again.')
  } finally {
    creating.value = false
  }
}

async function quickUpdateStatus(ticket: any, status: string) {
  const originalStatus = ticket.status
  ticket.status = status

  try {
    const response = await api.put(`${ADMIN_API_BASE_URL}/tickets/${ticket.id}`, { status })
    const updated = response.data.data || response.data
    tickets.value = tickets.value.map((t: any) => (t.id === updated.id ? updated : t))
    toastSuccess('Ticket status updated')
  } catch (err) {
    console.error('Failed to update ticket status:', err)
    ticket.status = originalStatus
    toastError('Failed to update ticket status. Please try again.')
  }
}

onMounted(() => {
  fetchTickets()
  fetchMetadata()
})
</script>


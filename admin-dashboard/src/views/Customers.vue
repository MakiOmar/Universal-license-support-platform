<template>
  <div>
    <div class="mb-4 flex justify-between items-center gap-4">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search customers..."
        class="px-4 py-2 border rounded-md w-full max-w-md"
      />
      <button
        @click="openCreateModal"
        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
      >
        Create Customer
      </button>
    </div>

    <!-- Create / Edit Customer Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click.self="closeModal"
    >
      <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ editingCustomer ? 'Edit Customer' : 'Create Customer' }}
          </h3>
          <form @submit.prevent="handleSubmit">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                <input
                  v-model="form.email"
                  type="email"
                  required
                  class="w-full px-3 py-2 border rounded-md"
                  placeholder="customer@example.com"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                <input
                  v-model="form.first_name"
                  type="text"
                  class="w-full px-3 py-2 border rounded-md"
                  placeholder="First name"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                <input
                  v-model="form.last_name"
                  type="text"
                  class="w-full px-3 py-2 border rounded-md"
                  placeholder="Last name"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Company</label>
                <input
                  v-model="form.company"
                  type="text"
                  class="w-full px-3 py-2 border rounded-md"
                  placeholder="Company name"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input
                  v-model="form.phone"
                  type="text"
                  class="w-full px-3 py-2 border rounded-md"
                  placeholder="+1 555 000 0000"
                />
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select v-model="form.status" class="w-full px-3 py-2 border rounded-md">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                  <option value="suspended">Suspended</option>
                </select>
              </div>
            </div>

            <div v-if="error" class="mt-4 text-red-600 text-sm">
              {{ error }}
            </div>

            <div class="mt-6 flex justify-end gap-2">
              <button
                type="button"
                @click="closeModal"
                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="saving"
                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
              >
                <span v-if="saving">{{ editingCustomer ? 'Saving...' : 'Creating...' }}</span>
                <span v-else>{{ editingCustomer ? 'Save Changes' : 'Create Customer' }}</span>
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
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Company</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="customer in filteredCustomers" :key="customer.id">
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ customer.email }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ getFullName(customer) }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ customer.company || 'N/A' }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getStatusClass(customer.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                {{ customer.status }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ formatDate(customer.created_at) }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm space-x-3">
              <router-link :to="`/customers/${customer.id}`" class="text-indigo-600 hover:text-indigo-900">View</router-link>
              <button
                type="button"
                @click="openEditModal(customer)"
                class="text-gray-700 hover:text-gray-900"
              >
                Edit
              </button>
              <button
                type="button"
                @click="handleDelete(customer)"
                class="text-red-600 hover:text-red-900"
              >
                Delete
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

const customers = ref([])
const loading = ref(false)
const searchQuery = ref('')
const showModal = ref(false)
const saving = ref(false)
const error = ref('')
const editingCustomer = ref<any | null>(null)

const form = ref({
  email: '',
  first_name: '',
  last_name: '',
  company: '',
  phone: '',
  status: 'active'
})

const filteredCustomers = computed(() => {
  if (!searchQuery.value) return customers.value
  const query = searchQuery.value.toLowerCase()
  return customers.value.filter((c: any) =>
    c.email?.toLowerCase().includes(query) ||
    c.first_name?.toLowerCase().includes(query) ||
    c.last_name?.toLowerCase().includes(query) ||
    c.company?.toLowerCase().includes(query)
  )
})

function getFullName(customer: any) {
  if (customer.first_name || customer.last_name) {
    return `${customer.first_name || ''} ${customer.last_name || ''}`.trim()
  }
  return 'N/A'
}

function getStatusClass(status: string) {
  const classes: Record<string, string> = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-800',
    suspended: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function formatDate(date: string | null) {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}

async function fetchCustomers() {
  loading.value = true
  try {
    const response = await api.get(`${ADMIN_API_BASE_URL}/customers`, { params: { per_page: 100 } })
    customers.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch customers:', error)
  } finally {
    loading.value = false
  }
}

function resetForm() {
  form.value = {
    email: '',
    first_name: '',
    last_name: '',
    company: '',
    phone: '',
    status: 'active'
  }
  error.value = ''
  editingCustomer.value = null
}

function openCreateModal() {
  resetForm()
  showModal.value = true
}

function openEditModal(customer: any) {
  editingCustomer.value = customer
  form.value = {
    email: customer.email || '',
    first_name: customer.first_name || '',
    last_name: customer.last_name || '',
    company: customer.company || '',
    phone: customer.phone || '',
    status: customer.status || 'active'
  }
  error.value = ''
  showModal.value = true
}

function closeModal() {
  if (saving.value) {
    return
  }

  showModal.value = false
}

async function handleSubmit() {
  saving.value = true
  error.value = ''

  try {
    if (editingCustomer.value) {
      const response = await api.put(
        `${ADMIN_API_BASE_URL}/customers/${editingCustomer.value.id}`,
        form.value
      )
      const updated = response.data.data || response.data
      customers.value = customers.value.map((c: any) =>
        c.id === updated.id ? updated : c
      )
    } else {
      const response = await api.post(`${ADMIN_API_BASE_URL}/customers`, form.value)
      const created = response.data.data || response.data
      customers.value.unshift(created)
    }

    showModal.value = false
  } catch (err: any) {
    if (err.response?.data?.message) {
      error.value = err.response.data.message
    } else if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      error.value = Object.values(errors).flat().join(', ')
    } else {
      error.value = 'Failed to save customer. Please try again.'
    }
  } finally {
    saving.value = false
  }
}

async function handleDelete(customer: any) {
  if (!confirm(`Are you sure you want to delete "${customer.email}"?`)) {
    return
  }

  try {
    await api.delete(`${ADMIN_API_BASE_URL}/customers/${customer.id}`)
    customers.value = customers.value.filter((c: any) => c.id !== customer.id)
  } catch (err: any) {
    alert('Failed to delete customer. Please try again.')
  }
}

onMounted(() => {
  fetchCustomers()
})
</script>


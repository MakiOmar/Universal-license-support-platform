<template>
  <div>
    <div class="mb-4">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search customers..."
        class="px-4 py-2 border rounded-md w-full max-w-md"
      />
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
            <td class="px-6 py-4 whitespace-nowrap text-sm">
              <router-link :to="`/customers/${customer.id}`" class="text-indigo-600 hover:text-indigo-900">View</router-link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '../services/api'

const customers = ref([])
const loading = ref(false)
const searchQuery = ref('')

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
    const response = await api.get('/customers', { params: { per_page: 100 } })
    customers.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch customers:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchCustomers()
})
</script>


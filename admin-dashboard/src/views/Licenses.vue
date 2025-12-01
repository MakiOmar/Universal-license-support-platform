<template>
  <div>
    <div class="mb-4 flex justify-between items-center">
      <div class="flex gap-4">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search licenses..."
          class="px-4 py-2 border rounded-md"
        />
        <select v-model="statusFilter" class="px-4 py-2 border rounded-md">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="pending">Pending</option>
          <option value="expired">Expired</option>
          <option value="suspended">Suspended</option>
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
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">License Key</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="license in filteredLicenses" :key="license.id">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono">{{ license.license_key }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ license.product?.name || 'N/A' }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ license.customer?.email || 'N/A' }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getStatusClass(license.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                {{ license.status }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ formatDate(license.expires_at) }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
              <router-link :to="`/licenses/${license.id}`" class="text-indigo-600 hover:text-indigo-900">View</router-link>
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

const licenses = ref([])
const loading = ref(false)
const searchQuery = ref('')
const statusFilter = ref('')

const filteredLicenses = computed(() => {
  let filtered = licenses.value

  if (statusFilter.value) {
    filtered = filtered.filter((l: any) => l.status === statusFilter.value)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter((l: any) =>
      l.license_key?.toLowerCase().includes(query) ||
      l.product?.name?.toLowerCase().includes(query) ||
      l.customer?.email?.toLowerCase().includes(query)
    )
  }

  return filtered
})

function getStatusClass(status: string) {
  const classes: Record<string, string> = {
    active: 'bg-green-100 text-green-800',
    pending: 'bg-yellow-100 text-yellow-800',
    expired: 'bg-red-100 text-red-800',
    suspended: 'bg-gray-100 text-gray-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function formatDate(date: string | null) {
  if (!date) return 'Never'
  return new Date(date).toLocaleDateString()
}

async function fetchLicenses() {
  loading.value = true
  try {
    const response = await api.get('/licenses', { params: { per_page: 100 } })
    licenses.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch licenses:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchLicenses()
})
</script>


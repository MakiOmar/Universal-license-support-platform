<template>
  <div>
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>

    <div v-else-if="customer" class="space-y-6">
      <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-semibold mb-4">Customer Details</h3>
        <dl class="grid grid-cols-2 gap-4">
          <div>
            <dt class="text-sm font-medium text-gray-500">Email</dt>
            <dd class="mt-1 text-sm">{{ customer.email }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Name</dt>
            <dd class="mt-1 text-sm">{{ getFullName(customer) }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Company</dt>
            <dd class="mt-1 text-sm">{{ customer.company || 'N/A' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Phone</dt>
            <dd class="mt-1 text-sm">{{ customer.phone || 'N/A' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Status</dt>
            <dd class="mt-1">
              <span :class="getStatusClass(customer.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                {{ customer.status }}
              </span>
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Created</dt>
            <dd class="mt-1 text-sm">{{ formatDate(customer.created_at) }}</dd>
          </div>
        </dl>
      </div>

      <div v-if="licenses.length > 0" class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-semibold mb-4">Licenses ({{ licenses.length }})</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">License Key</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="license in licenses" :key="license.id">
                <td class="px-4 py-3 text-sm font-mono">{{ license.license_key }}</td>
                <td class="px-4 py-3 text-sm">{{ license.product?.name || 'N/A' }}</td>
                <td class="px-4 py-3 text-sm">
                  <span :class="getStatusClass(license.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                    {{ license.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api, { ADMIN_API_BASE_URL } from '../services/api'

const route = useRoute()
const customer = ref<any>(null)
const licenses = ref([])
const loading = ref(false)

function getFullName(c: any) {
  if (c.first_name || c.last_name) {
    return `${c.first_name || ''} ${c.last_name || ''}`.trim()
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
  return new Date(date).toLocaleString()
}

async function fetchCustomer() {
  loading.value = true
  try {
    const response = await api.get(`${ADMIN_API_BASE_URL}/customers/${route.params.id}`)
    customer.value = response.data.data || response.data
    licenses.value = customer.value.licenses || []
  } catch (error) {
    console.error('Failed to fetch customer:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchCustomer()
})
</script>


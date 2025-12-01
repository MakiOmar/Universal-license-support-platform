<template>
  <div>
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>

    <div v-else-if="license" class="bg-white shadow rounded-lg p-6">
      <div class="mb-6">
        <h3 class="text-lg font-semibold mb-4">License Details</h3>
        <dl class="grid grid-cols-2 gap-4">
          <div>
            <dt class="text-sm font-medium text-gray-500">License Key</dt>
            <dd class="mt-1 text-sm font-mono">{{ license.license_key }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Status</dt>
            <dd class="mt-1">
              <span :class="getStatusClass(license.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                {{ license.status }}
              </span>
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Product</dt>
            <dd class="mt-1 text-sm">{{ license.product?.name || 'N/A' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Customer</dt>
            <dd class="mt-1 text-sm">{{ license.customer?.email || 'N/A' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Max Activations</dt>
            <dd class="mt-1 text-sm">{{ license.max_activations }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Current Activations</dt>
            <dd class="mt-1 text-sm">{{ license.current_activations || 0 }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Expires At</dt>
            <dd class="mt-1 text-sm">{{ formatDate(license.expires_at) }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Purchased At</dt>
            <dd class="mt-1 text-sm">{{ formatDate(license.purchased_at) }}</dd>
          </div>
        </dl>
      </div>

      <div v-if="activations.length > 0" class="mt-6">
        <h3 class="text-lg font-semibold mb-4">Activations</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Value</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Activated</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="activation in activations" :key="activation.id">
                <td class="px-4 py-3 text-sm">{{ activation.activation_type }}</td>
                <td class="px-4 py-3 text-sm font-mono">{{ activation.activation_value }}</td>
                <td class="px-4 py-3 text-sm">{{ activation.ip_address || 'N/A' }}</td>
                <td class="px-4 py-3 text-sm">{{ formatDate(activation.activated_at) }}</td>
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
import api from '../services/api'

const route = useRoute()
const license = ref<any>(null)
const activations = ref([])
const loading = ref(false)

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
  if (!date) return 'N/A'
  return new Date(date).toLocaleString()
}

async function fetchLicense() {
  loading.value = true
  try {
    const response = await api.get(`/licenses/${route.params.id}`)
    license.value = response.data.data || response.data
    activations.value = license.value.activations || []
  } catch (error) {
    console.error('Failed to fetch license:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchLicense()
})
</script>


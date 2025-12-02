<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
      <NuxtLink to="/licenses" class="text-indigo-600 hover:text-indigo-500 text-sm font-medium">
        ← Back to Licenses
      </NuxtLink>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>

    <div v-else-if="license" class="bg-white shadow rounded-lg overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h1 class="text-2xl font-bold text-gray-900">{{ license.product?.name || 'License Details' }}</h1>
          <span
            :class="{
              'bg-green-100 text-green-800': license.status === 'active',
              'bg-red-100 text-red-800': license.status === 'expired',
              'bg-yellow-100 text-yellow-800': license.status === 'pending',
              'bg-gray-100 text-gray-800': ['suspended', 'cancelled'].includes(license.status)
            }"
            class="px-3 py-1 text-sm font-semibold rounded-full"
          >
            {{ license.status }}
          </span>
        </div>
      </div>

      <div class="px-6 py-5">
        <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div>
            <dt class="text-sm font-medium text-gray-500">License Key</dt>
            <dd class="mt-1 text-sm text-gray-900 font-mono break-all">{{ license.license_key }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">License Type</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ license.license_type || 'N/A' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Product</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ license.product?.name || 'N/A' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Max Activations</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ license.max_activations || 'Unlimited' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Current Activations</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ license.current_activations || 0 }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Purchased At</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ formatDate(license.purchased_at) }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Expires At</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ formatDate(license.expires_at) }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Support Expires At</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ formatDate(license.support_expires_at) }}</dd>
          </div>
        </dl>
      </div>

      <!-- Activations -->
      <div v-if="license.activations && license.activations.length > 0" class="px-6 py-5 border-t border-gray-200">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Activation History</h2>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Value</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Activated</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="activation in license.activations" :key="activation.id">
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ activation.activation_type }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 font-mono">{{ activation.activation_value }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ activation.ip_address || 'N/A' }}</td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <span
                    :class="{
                      'bg-green-100 text-green-800': activation.status === 'active',
                      'bg-gray-100 text-gray-800': activation.status === 'inactive'
                    }"
                    class="px-2 py-1 text-xs font-semibold rounded-full"
                  >
                    {{ activation.status }}
                  </span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ formatDate(activation.activated_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth'
})

const route = useRoute()
const api = useApi()
const { CUSTOMER_API_BASE_URL } = useApi()

const loading = ref(true)
const license = ref<any>(null)

onMounted(async () => {
  await fetchLicense()
})

function formatDate(date: string | null) {
  if (!date) return 'Never'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

async function fetchLicense() {
  loading.value = true
  try {
    const response = await api.get(`${CUSTOMER_API_BASE_URL}/licenses/${route.params.id}`)
    license.value = response.data || response
  } catch (error) {
    console.error('Failed to fetch license:', error)
  } finally {
    loading.value = false
  }
}
</script>


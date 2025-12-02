<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">My Licenses</h1>
      <p class="mt-2 text-sm text-gray-600">Manage your software licenses</p>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>

    <div v-else-if="licenses.length === 0" class="bg-white shadow rounded-lg p-8 text-center">
      <p class="text-gray-500">You don't have any licenses yet.</p>
    </div>

    <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="license in licenses"
        :key="license.id"
        class="bg-white shadow rounded-lg overflow-hidden hover:shadow-md transition-shadow"
      >
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">
              {{ license.product?.name || 'Unknown Product' }}
            </h3>
            <span
              :class="{
                'bg-green-100 text-green-800': license.status === 'active',
                'bg-red-100 text-red-800': license.status === 'expired',
                'bg-yellow-100 text-yellow-800': license.status === 'pending',
                'bg-gray-100 text-gray-800': ['suspended', 'cancelled'].includes(license.status)
              }"
              class="px-2 py-1 text-xs font-semibold rounded-full"
            >
              {{ license.status }}
            </span>
          </div>

          <div class="space-y-2 mb-4">
            <div>
              <p class="text-xs text-gray-500">License Key</p>
              <p class="text-sm font-mono text-gray-900 break-all">{{ license.license_key }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Type</p>
              <p class="text-sm text-gray-900">{{ license.license_type || 'N/A' }}</p>
            </div>
            <div v-if="license.expires_at">
              <p class="text-xs text-gray-500">Expires</p>
              <p class="text-sm text-gray-900">{{ formatDate(license.expires_at) }}</p>
            </div>
            <div v-if="license.support_expires_at">
              <p class="text-xs text-gray-500">Support Expires</p>
              <p class="text-sm text-gray-900">{{ formatDate(license.support_expires_at) }}</p>
            </div>
          </div>

          <div class="mt-4">
            <NuxtLink
              :to="`/licenses/${license.id}`"
              class="block w-full text-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm font-medium"
            >
              View Details
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth'
})

const api = useApi()
const { CUSTOMER_API_BASE_URL } = useApi()

const loading = ref(true)
const licenses = ref<any[]>([])

onMounted(async () => {
  await fetchLicenses()
})

function formatDate(date: string | null) {
  if (!date) return 'Never'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

async function fetchLicenses() {
  loading.value = true
  try {
    const response = await api.get<{ data: any[] }>(`${CUSTOMER_API_BASE_URL}/licenses`)
    licenses.value = response.data || []
  } catch (error) {
    console.error('Failed to fetch licenses:', error)
  } finally {
    loading.value = false
  }
}
</script>


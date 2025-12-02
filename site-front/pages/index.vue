<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
      <p class="mt-2 text-sm text-gray-600">
        Welcome back, {{ authStore.fullName || authStore.customer?.email }}!
      </p>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>

    <div v-else class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <!-- Statistics Cards -->
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-indigo-500 rounded-md flex items-center justify-center">
                <span class="text-white text-sm font-bold">L</span>
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Licenses</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.totalLicenses }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                <span class="text-white text-sm font-bold">A</span>
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Active Licenses</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.activeLicenses }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                <span class="text-white text-sm font-bold">T</span>
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Open Tickets</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.openTickets }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                <span class="text-white text-sm font-bold">E</span>
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Expired Licenses</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.expiredLicenses }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Licenses -->
    <div class="mt-8">
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
          <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Licenses</h3>
        </div>
        <div v-if="recentLicenses.length === 0" class="p-6 text-center text-gray-500">
          No licenses found. <NuxtLink to="/licenses" class="text-indigo-600 hover:text-indigo-500">View all licenses</NuxtLink>
        </div>
        <ul v-else class="divide-y divide-gray-200">
          <li v-for="license in recentLicenses" :key="license.id" class="px-4 py-4 sm:px-6">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="h-10 w-10 rounded-md bg-indigo-100 flex items-center justify-center">
                    <span class="text-indigo-600 font-mono text-xs">{{ license.license_key?.substring(0, 4) }}</span>
                  </div>
                </div>
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900">{{ license.product?.name || 'N/A' }}</div>
                  <div class="text-sm text-gray-500 font-mono">{{ license.license_key }}</div>
                </div>
              </div>
              <div class="flex items-center space-x-4">
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
                <NuxtLink
                  :to="`/licenses/${license.id}`"
                  class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                >
                  View
                </NuxtLink>
              </div>
            </div>
          </li>
        </ul>
        <div v-if="recentLicenses.length > 0" class="px-4 py-3 bg-gray-50 text-center text-sm">
          <NuxtLink to="/licenses" class="text-indigo-600 hover:text-indigo-500 font-medium">
            View all licenses →
          </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth'
})

const authStore = useAuthStore()
const api = useApi()
const { CUSTOMER_API_BASE_URL } = api

const loading = ref(true)
const stats = ref({
  totalLicenses: 0,
  activeLicenses: 0,
  openTickets: 0,
  expiredLicenses: 0,
})
const recentLicenses = ref<any[]>([])

onMounted(async () => {
  await fetchDashboardData()
})

async function fetchDashboardData() {
  loading.value = true
  try {
    // Fetch customer's licenses using customer API
    const licensesRes = await api.get<{ data: any[] }>(`${CUSTOMER_API_BASE_URL}/licenses`)
    const licenses = licensesRes.data || []

    // Fetch customer's tickets using customer API
    const ticketsRes = await api.get<{ data: any[] }>(`${CUSTOMER_API_BASE_URL}/tickets`)
    const tickets = ticketsRes.data || []

    // Calculate statistics
    stats.value = {
      totalLicenses: licenses.length,
      activeLicenses: licenses.filter((l: any) => l.status === 'active').length,
      expiredLicenses: licenses.filter((l: any) => l.status === 'expired').length,
      openTickets: tickets.filter((t: any) => ['open', 'in_progress'].includes(t.status)).length,
    }

    // Get recent licenses (last 5)
    recentLicenses.value = licenses.slice(0, 5)
  } catch (error) {
    console.error('Failed to fetch dashboard data:', error)
  } finally {
    loading.value = false
  }
}
</script>


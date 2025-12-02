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

        <div class="mt-6 pt-6 border-t">
          <h4 class="text-md font-semibold mb-3">Renew License</h4>
          <form @submit.prevent="handleRenew" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Renewal Period Value *</label>
                <input
                  v-model.number="renewalForm.period_value"
                  type="number"
                  min="1"
                  required
                  class="w-full px-3 py-2 border rounded-md"
                  placeholder="e.g., 1"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Period Unit *</label>
                <select
                  v-model="renewalForm.period_unit"
                  required
                  class="w-full px-3 py-2 border rounded-md"
                >
                  <option value="">Select unit</option>
                  <option value="day">Day</option>
                  <option value="days">Days</option>
                  <option value="month">Month</option>
                  <option value="months">Months</option>
                  <option value="year">Year</option>
                  <option value="years">Years</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount (Optional)</label>
                <input
                  v-model.number="renewalForm.amount"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-3 py-2 border rounded-md"
                  placeholder="0.00"
                />
              </div>
            </div>
            <div class="flex items-center gap-4">
              <label class="inline-flex items-center text-sm text-gray-700">
                <input
                  v-model="renewalForm.create_payment"
                  type="checkbox"
                  class="rounded border-gray-300 mr-2"
                />
                Create payment record
              </label>
              <select
                v-if="renewalForm.create_payment"
                v-model="renewalForm.payment_method"
                class="px-3 py-2 border rounded-md text-sm"
              >
                <option value="manual">Manual</option>
                <option value="stripe">Stripe</option>
                <option value="paypal">PayPal</option>
              </select>
            </div>
            <div v-if="renewalError" class="text-sm text-red-600">
              {{ renewalError }}
            </div>
            <div class="flex justify-end">
              <button
                type="submit"
                :disabled="renewing"
                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50"
              >
                <span v-if="renewing">Renewing...</span>
                <span v-else>Renew License</span>
              </button>
            </div>
          </form>
        </div>
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
import api, { ADMIN_API_BASE_URL } from '../services/api'
import { useAlerts } from '../utils/alerts'

const route = useRoute()
const license = ref<any>(null)
const activations = ref([])
const loading = ref(false)
const renewing = ref(false)
const renewalError = ref('')

const { toastSuccess, toastError } = useAlerts()

const renewalForm = ref({
  period_value: 1,
  period_unit: 'year',
  amount: 0,
  create_payment: false,
  payment_method: 'manual'
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
  if (!date) return 'N/A'
  return new Date(date).toLocaleString()
}

async function fetchLicense() {
  loading.value = true
  try {
    const response = await api.get(`${ADMIN_API_BASE_URL}/licenses/${route.params.id}`)
    license.value = response.data.data || response.data
    activations.value = license.value.activations || []
  } catch (error) {
    console.error('Failed to fetch license:', error)
  } finally {
    loading.value = false
  }
}

async function handleRenew() {
  if (!license.value) {
    return
  }

  renewing.value = true
  renewalError.value = ''

  try {
    const payload: any = {
      renewal_period_value: renewalForm.value.period_value,
      renewal_period_unit: renewalForm.value.period_unit
    }

    if (renewalForm.value.create_payment && renewalForm.value.amount > 0) {
      payload.create_payment = true
      payload.amount = renewalForm.value.amount
      payload.payment_method = renewalForm.value.payment_method
    }

    const response = await api.post(
      `${ADMIN_API_BASE_URL}/licenses/${route.params.id}/renew`,
      payload
    )

    license.value = response.data.license || response.data.data || response.data
    toastSuccess('License renewed successfully')
    
    // Reset form
    renewalForm.value = {
      period_value: 1,
      period_unit: 'year',
      amount: 0,
      create_payment: false,
      payment_method: 'manual'
    }
  } catch (err: any) {
    if (err.response?.data?.message) {
      renewalError.value = err.response.data.message
    } else if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      renewalError.value = Object.values(errors).flat().join(', ')
    } else {
      renewalError.value = 'Failed to renew license. Please try again.'
    }
    toastError(renewalError.value || 'Failed to renew license. Please try again.')
  } finally {
    renewing.value = false
  }
}

onMounted(() => {
  fetchLicense()
})
</script>


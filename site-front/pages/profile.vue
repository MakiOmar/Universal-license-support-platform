<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Profile Settings</h1>
      <p class="mt-2 text-sm text-gray-600">Manage your account information</p>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>

    <div v-else class="bg-white shadow rounded-lg overflow-hidden">
      <form @submit.prevent="handleUpdateProfile" class="px-6 py-5 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
            <input
              v-model="form.email"
              type="email"
              required
              class="w-full px-3 py-2 border rounded-md"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
            <input
              v-model="form.phone"
              type="tel"
              class="w-full px-3 py-2 border rounded-md"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
            <input
              v-model="form.first_name"
              type="text"
              class="w-full px-3 py-2 border rounded-md"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
            <input
              v-model="form.last_name"
              type="text"
              class="w-full px-3 py-2 border rounded-md"
            />
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Company</label>
            <input
              v-model="form.company"
              type="text"
              class="w-full px-3 py-2 border rounded-md"
            />
          </div>
        </div>

        <div class="border-t border-gray-200 pt-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Change Password</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
              <input
                v-model="form.password"
                type="password"
                minlength="8"
                class="w-full px-3 py-2 border rounded-md"
                placeholder="Leave blank to keep current"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
              <input
                v-model="form.password_confirmation"
                type="password"
                class="w-full px-3 py-2 border rounded-md"
                placeholder="Confirm new password"
              />
            </div>
          </div>
        </div>

        <div v-if="error" class="text-sm text-red-600">
          {{ error }}
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t border-gray-200">
          <button
            type="button"
            @click="resetForm"
            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300"
          >
            Reset
          </button>
          <button
            type="submit"
            :disabled="saving"
            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
          >
            <span v-if="saving">Saving...</span>
            <span v-else>Save Changes</span>
          </button>
        </div>
      </form>
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
const { toastSuccess, toastError } = useAlerts()

const loading = ref(true)
const saving = ref(false)
const error = ref('')

const form = ref({
  email: '',
  first_name: '',
  last_name: '',
  company: '',
  phone: '',
  password: '',
  password_confirmation: '',
})

onMounted(async () => {
  await fetchProfile()
})

async function fetchProfile() {
  loading.value = true
  try {
    const response = await api.get(`${CUSTOMER_API_BASE_URL}/me`)
    const customer = response.data || response
    form.value = {
      email: customer.email || '',
      first_name: customer.first_name || '',
      last_name: customer.last_name || '',
      company: customer.company || '',
      phone: customer.phone || '',
      password: '',
      password_confirmation: '',
    }
  } catch (error) {
    console.error('Failed to fetch profile:', error)
    toastError('Failed to load profile')
  } finally {
    loading.value = false
  }
}

function resetForm() {
  fetchProfile()
}

async function handleUpdateProfile() {
  if (form.value.password && form.value.password !== form.value.password_confirmation) {
    error.value = 'Passwords do not match'
    return
  }

  saving.value = true
  error.value = ''

  try {
    const payload: any = {
      email: form.value.email,
      first_name: form.value.first_name,
      last_name: form.value.last_name,
      company: form.value.company,
      phone: form.value.phone,
    }

    if (form.value.password) {
      payload.password = form.value.password
      payload.password_confirmation = form.value.password_confirmation
    }

    const response = await api.put(`${CUSTOMER_API_BASE_URL}/profile`, payload)
    const updated = response.data || response
    
    // Update auth store with new customer data
    authStore.setAuth(updated, authStore.token!)
    
    toastSuccess('Profile updated successfully')
    form.value.password = ''
    form.value.password_confirmation = ''
  } catch (err: any) {
    if (err.message) {
      error.value = err.message
    } else if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      error.value = Object.values(errors).flat().join(', ')
    } else {
      error.value = 'Failed to update profile. Please try again.'
    }
    toastError(error.value)
  } finally {
    saving.value = false
  }
}
</script>


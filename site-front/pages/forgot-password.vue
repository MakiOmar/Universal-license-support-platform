<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Reset your password
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Enter your email address and we'll send you a password reset link.
        </p>
      </div>
      <form class="mt-8 space-y-6" @submit.prevent="handleForgotPassword">
        <div>
          <label for="email" class="sr-only">Email address</label>
          <input
            id="email"
            v-model="form.email"
            name="email"
            type="email"
            autocomplete="email"
            required
            class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            placeholder="Email address"
          />
        </div>

        <div v-if="success" class="text-sm text-green-600 text-center">
          {{ success }}
        </div>
        <div v-if="error" class="text-sm text-red-600 text-center">
          {{ error }}
        </div>

        <div>
          <button
            type="submit"
            :disabled="loading"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            <span v-if="loading">Sending...</span>
            <span v-else>Send reset link</span>
          </button>
        </div>

        <div class="text-center">
          <NuxtLink to="/login" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
            Back to login
          </NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: false,
  middleware: 'auth'
})

const api = useApi()
const { toastSuccess, toastError } = useAlerts()

const form = ref({
  email: '',
})

const loading = ref(false)
const error = ref('')
const success = ref('')

async function handleForgotPassword() {
  loading.value = true
  error.value = ''
  success.value = ''

  try {
    const response = await api.post('/auth/forgot-password', form.value)
    success.value = response.message || 'If the email exists, a password reset link has been sent.'
    toastSuccess(success.value)
  } catch (err: any) {
    error.value = err.message || 'Failed to send reset link. Please try again.'
    toastError(error.value)
  } finally {
    loading.value = false
  }
}
</script>


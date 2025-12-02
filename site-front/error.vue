<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full text-center">
      <h1 class="text-6xl font-bold text-gray-900">{{ error.statusCode || 'Error' }}</h1>
      <h2 class="mt-4 text-2xl font-semibold text-gray-900">{{ error.statusMessage || 'Something went wrong' }}</h2>
      <p class="mt-4 text-sm text-gray-600">
        {{ error.message || 'An unexpected error occurred. Please try again later.' }}
      </p>
      <div class="mt-8">
        <button
          @click="handleError"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
        >
          Go Home
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: false
})

const props = defineProps<{
  error: {
    statusCode?: number
    statusMessage?: string
    message?: string
  }
}>()

function handleError() {
  clearError({ redirect: '/' })
}
</script>


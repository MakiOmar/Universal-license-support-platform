<template>
  <div style="padding: 20px; background: #f0f0f0;">
    <h1 style="color: red; font-size: 24px;">Test Page - If you see this, the app is working!</h1>
    <p>Current route: {{ $route.path }}</p>
    <p>Auth status: {{ authStore.isAuthenticated ? 'Authenticated' : 'Not authenticated' }}</p>
    <NuxtLink to="/">Go to Home</NuxtLink> | 
    <NuxtLink to="/login">Go to Login</NuxtLink>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: false,
  middleware: []
})

const authStore = useAuthStore()
</script>


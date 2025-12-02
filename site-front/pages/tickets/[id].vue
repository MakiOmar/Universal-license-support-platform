<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
      <NuxtLink to="/tickets" class="text-indigo-600 hover:text-indigo-500 text-sm font-medium">
        ← Back to Tickets
      </NuxtLink>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>

    <div v-else-if="ticket" class="space-y-6">
      <!-- Ticket Header -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">{{ ticket.subject }}</h1>
              <p class="mt-1 text-sm text-gray-500">Ticket #{{ ticket.ticket_number }}</p>
            </div>
            <div class="flex items-center gap-3">
              <span
                :class="{
                  'bg-gray-100 text-gray-800': ticket.priority === 'low',
                  'bg-yellow-100 text-yellow-800': ticket.priority === 'medium',
                  'bg-orange-100 text-orange-800': ticket.priority === 'high',
                  'bg-red-100 text-red-800': ticket.priority === 'urgent'
                }"
                class="px-3 py-1 text-sm font-semibold rounded-full"
              >
                {{ ticket.priority }}
              </span>
              <span
                :class="{
                  'bg-blue-100 text-blue-800': ticket.status === 'open',
                  'bg-yellow-100 text-yellow-800': ticket.status === 'in_progress',
                  'bg-purple-100 text-purple-800': ticket.status === 'waiting_customer',
                  'bg-green-100 text-green-800': ticket.status === 'resolved',
                  'bg-gray-100 text-gray-800': ticket.status === 'closed'
                }"
                class="px-3 py-1 text-sm font-semibold rounded-full"
              >
                {{ ticket.status }}
              </span>
            </div>
          </div>
        </div>
        <div class="px-6 py-5">
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <span class="text-gray-500">Product:</span>
              <span class="ml-2 text-gray-900">{{ ticket.product?.name || 'N/A' }}</span>
            </div>
            <div>
              <span class="text-gray-500">Category:</span>
              <span class="ml-2 text-gray-900">{{ ticket.category || 'N/A' }}</span>
            </div>
            <div>
              <span class="text-gray-500">Created:</span>
              <span class="ml-2 text-gray-900">{{ formatDate(ticket.created_at) }}</span>
            </div>
            <div>
              <span class="text-gray-500">Last Updated:</span>
              <span class="ml-2 text-gray-900">{{ formatDate(ticket.updated_at) }}</span>
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-200">
            <p class="text-sm text-gray-500 mb-2">Description:</p>
            <p class="text-gray-900 whitespace-pre-wrap">{{ ticket.description }}</p>
          </div>
        </div>
      </div>

      <!-- Replies -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">Replies</h2>
        </div>
        <div class="divide-y divide-gray-200">
          <div
            v-for="reply in ticket.replies || []"
            :key="reply.id"
            class="px-6 py-4"
          >
            <div class="flex items-start justify-between mb-2">
              <div>
                <span class="text-sm font-medium text-gray-900">
                  {{ reply.user_type === 'customer' ? 'You' : 'Support Agent' }}
                </span>
                <span class="ml-2 text-xs text-gray-500">{{ formatDate(reply.created_at) }}</span>
              </div>
            </div>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ reply.message }}</p>
          </div>
        </div>
      </div>

      <!-- Add Reply Form -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">Add Reply</h2>
        </div>
        <form @submit.prevent="handleAddReply" class="px-6 py-5">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
            <textarea
              v-model="replyForm.message"
              required
              rows="4"
              maxlength="5000"
              class="w-full px-3 py-2 border rounded-md"
              placeholder="Type your reply here..."
            ></textarea>
          </div>
          <div v-if="replyError" class="mb-4 text-sm text-red-600">
            {{ replyError }}
          </div>
          <button
            type="submit"
            :disabled="replying"
            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
          >
            <span v-if="replying">Sending...</span>
            <span v-else>Send Reply</span>
          </button>
        </form>
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
const { CUSTOMER_API_BASE_URL } = api
const { toastSuccess, toastError } = useAlerts()

const loading = ref(true)
const ticket = ref<any>(null)
const replying = ref(false)
const replyError = ref('')
const replyForm = ref({
  message: '',
})

onMounted(async () => {
  await fetchTicket()
})

function formatDate(date: string | null) {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

async function fetchTicket() {
  loading.value = true
  try {
    const response = await api.get(`${CUSTOMER_API_BASE_URL}/tickets/${route.params.id}`)
    ticket.value = response.data || response
  } catch (error) {
    console.error('Failed to fetch ticket:', error)
    toastError('Failed to load ticket')
  } finally {
    loading.value = false
  }
}

async function handleAddReply() {
  replying.value = true
  replyError.value = ''

  try {
    await api.post(`${CUSTOMER_API_BASE_URL}/tickets/${route.params.id}/replies`, {
      message: replyForm.value.message,
    })
    toastSuccess('Reply sent successfully')
    replyForm.value.message = ''
    await fetchTicket() // Refresh ticket to get new reply
  } catch (err: any) {
    replyError.value = err.message || 'Failed to send reply. Please try again.'
    toastError(replyError.value)
  } finally {
    replying.value = false
  }
}
</script>


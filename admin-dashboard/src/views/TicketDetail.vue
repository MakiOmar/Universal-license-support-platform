<template>
  <div>
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>

    <div v-else-if="ticket" class="space-y-6">
      <div class="bg-white shadow rounded-lg p-6">
        <div class="mb-4">
          <h3 class="text-lg font-semibold">{{ ticket.subject }}</h3>
          <p class="text-sm text-gray-500">Ticket #{{ ticket.ticket_number }}</p>
        </div>
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <span class="text-sm font-medium text-gray-500">Status: </span>
            <span :class="getStatusClass(ticket.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
              {{ ticket.status }}
            </span>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-500">Priority: </span>
            <span :class="getPriorityClass(ticket.priority)" class="px-2 py-1 text-xs font-semibold rounded-full">
              {{ ticket.priority }}
            </span>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-500">Customer: </span>
            <span class="text-sm">{{ ticket.customer?.email || 'N/A' }}</span>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-500">Created: </span>
            <span class="text-sm">{{ formatDate(ticket.created_at) }}</span>
          </div>
          <div>
            <span class="text-sm font-medium text-gray-500">Assigned To: </span>
            <select
              v-model="assignedAdminId"
              @change="handleAssignTicket"
              class="text-sm border rounded px-2 py-1 ml-2"
            >
              <option :value="null">Unassigned</option>
              <option
                v-for="admin in admins"
                :key="admin.id"
                :value="admin.id"
              >
                {{ admin.name }}
              </option>
            </select>
          </div>
        </div>
        <div class="border-t pt-4">
          <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ ticket.description }}</p>
        </div>
      </div>

      <div class="bg-white shadow rounded-lg p-6 space-y-6">
        <div>
          <h3 class="text-lg font-semibold mb-4">Replies</h3>
          <div v-if="replies.length === 0" class="text-gray-500 text-sm">No replies yet</div>
          <div v-else class="space-y-4">
            <div v-for="reply in replies" :key="reply.id" class="border-l-4 border-indigo-500 pl-4">
              <div class="flex justify-between mb-2">
                <span class="text-sm font-medium capitalize">{{ reply.user_type }}</span>
                <span class="text-sm text-gray-500">{{ formatDate(reply.created_at) }}</span>
              </div>
              <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ reply.message }}</p>
              <div v-if="reply.attachments && reply.attachments.length > 0" class="mt-3 space-y-2">
                <div class="text-xs font-medium text-gray-600">Attachments:</div>
                <div class="flex flex-wrap gap-2">
                  <a
                    v-for="attachment in reply.attachments"
                    :key="attachment.id"
                    :href="attachment.url"
                    target="_blank"
                    class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded text-xs text-gray-700"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                    {{ attachment.filename }}
                    <span class="text-gray-500">({{ formatFileSize(attachment.file_size) }})</span>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="border-t pt-4">
          <h4 class="text-md font-semibold mb-3">Add Reply</h4>
          <form @submit.prevent="handleReplySubmit" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
              <textarea
                v-model="replyForm.message"
                rows="4"
                required
                class="w-full px-3 py-2 border rounded-md"
                placeholder="Type your reply to the customer..."
              ></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Attachments</label>
              <input
                type="file"
                ref="fileInput"
                @change="handleFileSelect"
                multiple
                accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.log"
                class="w-full px-3 py-2 border rounded-md text-sm"
              />
              <div v-if="selectedFiles.length > 0" class="mt-2 space-y-1">
                <div
                  v-for="(file, index) in selectedFiles"
                  :key="index"
                  class="flex items-center justify-between text-xs bg-gray-50 px-2 py-1 rounded"
                >
                  <span class="text-gray-700">{{ file.name }}</span>
                  <button
                    type="button"
                    @click="removeFile(index)"
                    class="text-red-600 hover:text-red-800"
                  >
                    Remove
                  </button>
                </div>
              </div>
              <p class="text-xs text-gray-500 mt-1">
                Supported formats: JPG, PNG, GIF, PDF, DOC, DOCX, TXT, LOG (Max 10MB per file)
              </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select v-model="replyForm.status" class="w-full px-3 py-2 border rounded-md">
                  <option value="open">Open</option>
                  <option value="in_progress">In Progress</option>
                  <option value="waiting_customer">Waiting Customer</option>
                  <option value="resolved">Resolved</option>
                  <option value="closed">Closed</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                <select v-model="replyForm.priority" class="w-full px-3 py-2 border rounded-md">
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
              <div class="flex items-center mt-6 md:mt-0">
                <label class="inline-flex items-center text-sm text-gray-700">
                  <input
                    v-model="replyForm.is_internal"
                    type="checkbox"
                    class="rounded border-gray-300 mr-2"
                  />
                  Internal note (customer cannot see)
                </label>
              </div>
            </div>

            <div v-if="replyError" class="text-sm text-red-600">
              {{ replyError }}
            </div>

            <div class="flex justify-between items-center">
              <button
                type="button"
                @click="handleCloseTicket"
                :disabled="replySaving || closeSaving || ticket.status === 'closed'"
                class="px-4 py-2 border border-gray-300 text-gray-800 rounded-md hover:bg-gray-100 disabled:opacity-50"
              >
                <span v-if="closeSaving">Closing...</span>
                <span v-else>Close Ticket</span>
              </button>

              <button
                type="submit"
                :disabled="replySaving || uploadingFiles"
                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
              >
                <span v-if="replySaving || uploadingFiles">Sending...</span>
                <span v-else>Send Reply</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api, { ADMIN_API_BASE_URL } from '../services/api'
import { useAuthStore } from '../stores/auth'
import { useAlerts } from '../utils/alerts'

const route = useRoute()
const ticket = ref<any>(null)
const replies = ref([])
const loading = ref(false)
const replySaving = ref(false)
const closeSaving = ref(false)
const replyError = ref('')
const admins = ref<any[]>([])
const assignedAdminId = ref<number | null>(null)
const assigning = ref(false)
const selectedFiles = ref<File[]>([])
const fileInput = ref<HTMLInputElement | null>(null)
const uploadingFiles = ref(false)

const authStore = useAuthStore()
const { toastSuccess, toastError, confirmAction } = useAlerts()

const replyForm = ref({
  message: '',
  status: 'open',
  priority: 'medium',
  is_internal: false
})

function getStatusClass(status: string) {
  const classes: Record<string, string> = {
    open: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-yellow-100 text-yellow-800',
    resolved: 'bg-green-100 text-green-800',
    closed: 'bg-gray-100 text-gray-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function getPriorityClass(priority: string) {
  const classes: Record<string, string> = {
    low: 'bg-gray-100 text-gray-800',
    medium: 'bg-yellow-100 text-yellow-800',
    high: 'bg-orange-100 text-orange-800',
    urgent: 'bg-red-100 text-red-800'
  }
  return classes[priority] || 'bg-gray-100 text-gray-800'
}

function formatDate(date: string | null) {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString()
}

function formatFileSize(bytes: number | null) {
  if (!bytes) return '0 B'
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

function handleFileSelect(event: Event) {
  const target = event.target as HTMLInputElement
  if (target.files) {
    selectedFiles.value = Array.from(target.files)
  }
}

function removeFile(index: number) {
  selectedFiles.value.splice(index, 1)
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

async function fetchTicket() {
  loading.value = true
  try {
    const response = await api.get(`${ADMIN_API_BASE_URL}/tickets/${route.params.id}`)
    ticket.value = response.data.data || response.data
    replies.value = ticket.value.replies || []
    assignedAdminId.value = ticket.value.assigned_to || null
  } catch (error) {
    console.error('Failed to fetch ticket:', error)
  } finally {
    loading.value = false
  }
}

async function fetchAdmins() {
  try {
    const response = await api.get(`${ADMIN_API_BASE_URL}/admins`)
    admins.value = response.data || []
  } catch (error) {
    console.error('Failed to fetch admins:', error)
  }
}

async function handleAssignTicket() {
  if (!ticket.value) {
    return
  }

  assigning.value = true
  const originalAssignedTo = ticket.value.assigned_to

  try {
    const response = await api.post(`${ADMIN_API_BASE_URL}/tickets/${route.params.id}/assign`, {
      assigned_to: assignedAdminId.value
    })
    ticket.value = response.data.data || response.data
    toastSuccess(assignedAdminId.value ? 'Ticket assigned successfully' : 'Ticket unassigned successfully')
  } catch (err: any) {
    assignedAdminId.value = originalAssignedTo
    if (err.response?.data?.message) {
      replyError.value = err.response.data.message
    } else {
      replyError.value = 'Failed to assign ticket. Please try again.'
    }
    toastError(replyError.value || 'Failed to assign ticket. Please try again.')
  } finally {
    assigning.value = false
  }
}

async function handleReplySubmit() {
  if (!ticket.value) {
    return
  }

  replySaving.value = true
  replyError.value = ''

  try {
    // Update ticket status / priority if changed
    const updatePayload: any = {}
    if (replyForm.value.status && replyForm.value.status !== ticket.value.status) {
      updatePayload.status = replyForm.value.status
    }
    if (replyForm.value.priority && replyForm.value.priority !== ticket.value.priority) {
      updatePayload.priority = replyForm.value.priority
    }

    if (Object.keys(updatePayload).length > 0) {
      const updateRes = await api.put(
        `${ADMIN_API_BASE_URL}/tickets/${route.params.id}`,
        updatePayload
      )
      ticket.value = updateRes.data.data || updateRes.data
    }

    const userId = authStore.user?.id || 0
    const replyRes = await api.post(
      `${ADMIN_API_BASE_URL}/tickets/${route.params.id}/replies`,
      {
        user_id: userId,
        user_type: 'agent',
        message: replyForm.value.message,
        is_internal: replyForm.value.is_internal
      }
    )

    const createdReply = replyRes.data.data || replyRes.data
    replies.value.push(createdReply)

    replyForm.value.message = ''
    replyForm.value.is_internal = false
    toastSuccess('Reply added successfully')
  } catch (err: any) {
    if (err.response?.data?.message) {
      replyError.value = err.response.data.message
    } else if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      replyError.value = Object.values(errors).flat().join(', ')
    } else {
      replyError.value = 'Failed to send reply. Please try again.'
    }
    toastError(replyError.value || 'Failed to send reply. Please try again.')
  } finally {
    replySaving.value = false
  }
}

async function handleCloseTicket() {
  if (!ticket.value || ticket.value.status === 'closed') {
    return
  }

  const confirmed = await confirmAction(
    'Close ticket?',
    'This will mark the ticket as closed.'
  )

  if (!confirmed) {
    return
  }

  closeSaving.value = true

  try {
    const response = await api.post(`${ADMIN_API_BASE_URL}/tickets/${route.params.id}/close`)
    ticket.value = response.data.data || response.data
  } catch (err: any) {
    // Surface via reply error area
    if (err.response?.data?.message) {
      replyError.value = err.response.data.message
    } else {
      replyError.value = 'Failed to close ticket. Please try again.'
    }
    toastError(replyError.value || 'Failed to close ticket. Please try again.')
  } finally {
    closeSaving.value = false
  }
}

onMounted(() => {
  fetchTicket()
  fetchAdmins()
})
</script>


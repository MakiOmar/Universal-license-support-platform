import { defineStore } from 'pinia'

interface Customer {
  id: number
  email: string
  first_name?: string
  last_name?: string
  company?: string
  phone?: string
  status: string
}

interface AuthState {
  customer: Customer | null
  token: string | null
  isAuthenticated: boolean
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    customer: null,
    token: null,
    isAuthenticated: false,
  }),

  getters: {
    fullName: (state) => {
      if (!state.customer) return ''
      const { first_name, last_name } = state.customer
      if (first_name || last_name) {
        return `${first_name || ''} ${last_name || ''}`.trim()
      }
      return state.customer.email
    },
  },

  actions: {
    setAuth(customer: Customer, token: string) {
      this.customer = customer
      this.token = token
      this.isAuthenticated = true

      // Store in localStorage for persistence
      if (process.client) {
        localStorage.setItem('auth_token', token)
        localStorage.setItem('auth_customer', JSON.stringify(customer))
      }
    },

    loadFromStorage() {
      if (process.client) {
        const token = localStorage.getItem('auth_token')
        const customerStr = localStorage.getItem('auth_customer')

        if (token && customerStr) {
          try {
            const customer = JSON.parse(customerStr)
            this.customer = customer
            this.token = token
            this.isAuthenticated = true
          } catch (e) {
            this.logout()
          }
        }
      }
    },

    logout() {
      this.customer = null
      this.token = null
      this.isAuthenticated = false

      if (process.client) {
        localStorage.removeItem('auth_token')
        localStorage.removeItem('auth_customer')
      }
    },
  },
})


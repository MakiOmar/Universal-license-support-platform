import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/api'

export const useDashboardStore = defineStore('dashboard', () => {
  const stats = ref({
    totalLicenses: 0,
    activeLicenses: 0,
    openTickets: 0,
    totalRevenue: 0
  })
  const loading = ref(false)

  async function fetchStats() {
    loading.value = true
    try {
      // Fetch licenses
      const licensesRes = await api.get('/licenses?per_page=1')
      const totalLicenses = licensesRes.data.total || 0
      
      const activeLicensesRes = await api.get('/licenses?per_page=1', {
        params: { status: 'active' }
      })
      const activeLicenses = activeLicensesRes.data.total || 0

      // Fetch tickets
      const ticketsRes = await api.get('/tickets?per_page=1', {
        params: { status: 'open' }
      })
      const openTickets = ticketsRes.data.total || 0

      // Fetch payments (if endpoint exists)
      let totalRevenue = 0
      try {
        const paymentsRes = await api.get('/payments?per_page=1', {
          params: { status: 'completed' }
        })
        // Calculate revenue from payments if available
        totalRevenue = paymentsRes.data.total || 0
      } catch (e) {
        // Payments endpoint might not be available
      }

      stats.value = {
        totalLicenses,
        activeLicenses,
        openTickets,
        totalRevenue
      }
    } catch (error) {
      console.error('Failed to fetch stats:', error)
    } finally {
      loading.value = false
    }
  }

  return {
    stats,
    loading,
    fetchStats
  }
})


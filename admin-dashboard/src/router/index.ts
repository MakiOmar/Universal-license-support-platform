import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'Login',
      component: () => import('../views/Login.vue'),
      meta: { requiresAuth: false }
    },
    {
      path: '/',
      component: () => import('../layouts/DashboardLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'Dashboard',
          component: () => import('../views/Dashboard.vue')
        },
        {
          path: 'licenses',
          name: 'Licenses',
          component: () => import('../views/Licenses.vue')
        },
        {
          path: 'licenses/:id',
          name: 'LicenseDetail',
          component: () => import('../views/LicenseDetail.vue')
        },
        {
          path: 'tickets',
          name: 'Tickets',
          component: () => import('../views/Tickets.vue')
        },
        {
          path: 'tickets/:id',
          name: 'TicketDetail',
          component: () => import('../views/TicketDetail.vue')
        },
        {
          path: 'customers',
          name: 'Customers',
          component: () => import('../views/Customers.vue')
        },
        {
          path: 'customers/:id',
          name: 'CustomerDetail',
          component: () => import('../views/CustomerDetail.vue')
        },
        {
          path: 'products',
          name: 'Products',
          component: () => import('../views/Products.vue')
        },
        {
          path: 'api-keys',
          name: 'ApiKeys',
          component: () => import('../views/ApiKeys.vue')
        }
      ]
    }
  ]
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'Login' })
  } else if (to.name === 'Login' && authStore.isAuthenticated) {
    next({ name: 'Dashboard' })
  } else {
    next()
  }
})

export default router


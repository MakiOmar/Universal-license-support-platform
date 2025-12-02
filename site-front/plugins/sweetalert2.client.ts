import Swal from 'sweetalert2'
import 'sweetalert2/dist/sweetalert2.min.css'

// Make Swal available globally if needed
export default defineNuxtPlugin(() => {
  return {
    provide: {
      swal: Swal
    }
  }
})


import Swal, { type SweetAlertIcon } from 'sweetalert2'
import 'sweetalert2/dist/sweetalert2.min.css'

export function useAlerts() {
  async function confirmAction(
    title: string,
    text = '',
    confirmButtonText = 'Yes',
    cancelButtonText = 'Cancel'
  ): Promise<boolean> {
    const result = await Swal.fire({
      title,
      text,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText,
      cancelButtonText,
      confirmButtonColor: '#4f46e5',
      cancelButtonColor: '#6b7280',
      reverseButtons: true,
      focusCancel: true
    })

    return result.isConfirmed
  }

  function showToast(message: string, icon: SweetAlertIcon = 'success') {
    return Swal.fire({
      toast: true,
      position: 'top-end',
      icon,
      title: message,
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true
    })
  }

  function toastSuccess(message: string) {
    return showToast(message, 'success')
  }

  function toastError(message: string) {
    return showToast(message, 'error')
  }

  return {
    confirmAction,
    toastSuccess,
    toastError,
    showToast
  }
}



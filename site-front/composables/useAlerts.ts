import Swal, { type SweetAlertIcon } from 'sweetalert2'

/**
 * Composable for SweetAlert2 confirmations and toast notifications
 * Use this across the customer portal for consistent UI feedback
 * 
 * @example
 * ```ts
 * const { confirmAction, toastSuccess, toastError } = useAlerts()
 * 
 * // Confirmation
 * const confirmed = await confirmAction('Delete License?', 'This action cannot be undone')
 * if (confirmed) { ... }
 * 
 * // Toast notifications
 * toastSuccess('License activated successfully!')
 * toastError('Failed to activate license')
 * ```
 */
export const useAlerts = () => {
  /**
   * Show a confirmation dialog
   * @param title - Dialog title
   * @param text - Dialog message
   * @param confirmButtonText - Confirm button text (default: "Yes")
   * @param cancelButtonText - Cancel button text (default: "Cancel")
   * @returns Promise<boolean> - true if confirmed, false if cancelled
   */
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

  /**
   * Show a toast notification
   * @param message - Toast message
   * @param icon - Icon type (success, error, warning, info)
   */
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

  /**
   * Show a success toast notification
   * @param message - Success message
   */
  function toastSuccess(message: string) {
    return showToast(message, 'success')
  }

  /**
   * Show an error toast notification
   * @param message - Error message
   */
  function toastError(message: string) {
    return showToast(message, 'error')
  }

  /**
   * Show an info toast notification
   * @param message - Info message
   */
  function toastInfo(message: string) {
    return showToast(message, 'info')
  }

  /**
   * Show a warning toast notification
   * @param message - Warning message
   */
  function toastWarning(message: string) {
    return showToast(message, 'warning')
  }

  return {
    confirmAction,
    toastSuccess,
    toastError,
    toastInfo,
    toastWarning,
    showToast
  }
}


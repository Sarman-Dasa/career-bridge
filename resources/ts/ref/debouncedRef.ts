import { customRef } from 'vue'

/**
 * Creates a debounced ref that only updates its value after a specified delay
 * @param value Initial value for the ref
 * @param delay Delay in milliseconds before updating the value (default: 200ms)
 * @returns A custom ref with debounced value updates
 */
export function useDebouncedRef(value: any, delay = 200) {
  // Track the timeout to clear it on new updates
  let timeout: number | undefined
  
  return customRef((track, trigger) => {
    return {
      // Get function tracks dependencies and returns current value
      get() {
        track()
        return value
      },
      // Set function debounces value updates
      set(newValue) {
        // Clear any existing timeout
        clearTimeout(timeout)
        // Set new timeout to update value after delay
        timeout = setTimeout(() => {
          value = newValue
          trigger() // Notify subscribers of change
        }, delay)
      }
    }
  })
}

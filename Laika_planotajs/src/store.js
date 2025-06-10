import { ref, watch } from 'vue';

// Initialize user from localStorage if available
export const user = ref(JSON.parse(localStorage.getItem('user')) || null);

// Watch for changes to user and update localStorage automatically
watch(user, (newValue) => {
  if (newValue) {
    localStorage.setItem('user', JSON.stringify(newValue));
  } else {
    localStorage.removeItem('user');
  }
}, { deep: true });

/**
 * Update user data in the store
 * @param {Object} userData - The new user data to merge with existing data
 */
export function updateUser(userData) {
  if (user.value && userData) {
    user.value = { ...user.value, ...userData };
    // No need to manually update localStorage due to the watcher above
    
    // Dispatch event for components that don't directly use the store
    window.dispatchEvent(new CustomEvent('user-updated'));
  }
}

/**
 * Set the user (used during login)
 * @param {Object} userData - Complete user data
 */
export function setUser(userData) {
  user.value = userData;
}

/**
 * Clear user data (used during logout)
 */
export function clearUser() {
  user.value = null;
}

// Listen for storage events from other tabs/windows
if (typeof window !== 'undefined') {
  window.addEventListener('storage', (event) => {
    if (event.key === 'user') {
      user.value = event.newValue ? JSON.parse(event.newValue) : null;
    }
  });
}
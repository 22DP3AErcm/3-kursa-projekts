<template>
  <div id="app">
    <header>
      <nav>
        <RouterLink to="/" class="nav-button">Home</RouterLink>
        <RouterLink to="/about" class="nav-button">About</RouterLink>
        <!-- Show Admin button in main nav only for admin users -->
        <RouterLink v-if="user && user.is_admin" to="/admin" class="nav-button admin-button">Admin</RouterLink>
      </nav>
      <div class="header-right">
        <template v-if="user && user.name">
          <div class="dropdown" ref="dropdown">
            <button 
              class="nav-button" 
              @click="toggleDropdown"
              @touchstart="toggleDropdown"
            >
              {{ truncateName(user.name) }}
            </button>
            <div class="dropdown-content" :class="{ 'show': isDropdownOpen }">
              <RouterLink to="/settings" class="dropdown-item" @click="closeDropdown">Settings</RouterLink>
              <RouterLink to="/mail" class="dropdown-item" @click="closeDropdown">Mail</RouterLink>
              <!-- Show Admin link only for admin users -->
              <RouterLink v-if="user.is_admin" to="/admin" class="dropdown-item admin-item" @click="closeDropdown">Admin</RouterLink>
              <button @click="logout" class="dropdown-item">Log Out</button>
            </div>
          </div>
        </template>
        <template v-else>
          <RouterLink to="/login" class="nav-button">Login</RouterLink>
        </template>
      </div>
    </header>
    <main>
      <RouterView />
    </main>
  </div>
</template>

<script setup>
import { RouterLink, RouterView, useRouter } from 'vue-router';
import { ref, onMounted, onUnmounted } from 'vue';
import '@/assets/styles.css';

const router = useRouter();
const user = ref(JSON.parse(localStorage.getItem('user')) || null);
const isDropdownOpen = ref(false);
const dropdown = ref(null);

// Truncate name for mobile display
const truncateName = (name) => {
  if (window.innerWidth <= 480 && name && name.length > 8) {
    return name.substring(0, 8) + '...';
  }
  return name;
};

const toggleDropdown = (event) => {
  event.preventDefault();
  event.stopPropagation();
  isDropdownOpen.value = !isDropdownOpen.value;
};

const closeDropdown = () => {
  isDropdownOpen.value = false;
};

const handleClickOutside = (event) => {
  if (dropdown.value && !dropdown.value.contains(event.target)) {
    closeDropdown();
  }
};

const logout = async () => {
  user.value = null;
  localStorage.removeItem('user');
  closeDropdown();
  await router.push('/login');
  window.location.reload();
};

// Function to update user data from localStorage
const updateUserFromStorage = () => {
  const storedUser = JSON.parse(localStorage.getItem('user'));
  if (storedUser) {
    user.value = storedUser;
  }
};

// Add event listener for user updates
const handleUserUpdate = () => {
  updateUserFromStorage();
};

onMounted(() => {
  updateUserFromStorage();
  
  // Listen for user profile updates
  window.addEventListener('user-updated', handleUserUpdate);
  
  // Add click outside listener
  document.addEventListener('click', handleClickOutside);
  document.addEventListener('touchstart', handleClickOutside);
  
  // Add viewport meta tag if not present
  if (!document.querySelector('meta[name="viewport"]')) {
    const meta = document.createElement('meta');
    meta.name = 'viewport';
    meta.content = 'width=device-width, initial-scale=1.0';
    document.getElementsByTagName('head')[0].appendChild(meta);
  }
});

onUnmounted(() => {
  // Clean up event listeners
  window.removeEventListener('user-updated', handleUserUpdate);
  document.removeEventListener('click', handleClickOutside);
  document.removeEventListener('touchstart', handleClickOutside);
});
</script>
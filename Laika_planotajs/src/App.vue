<template>
  <div id="app">
    <header>
      <div class="header-right">
        <template v-if="user && user.name">
          <div class="dropdown">
            <button class="nav-button">{{ user.name }}</button>
            <div class="dropdown-content">
              <RouterLink to="/settings" class="dropdown-item">Settings</RouterLink>
              <RouterLink to="/mail" class="dropdown-item">Mail</RouterLink>
              <button @click="logout" class="dropdown-item">Log Out</button>
            </div>
          </div>
        </template>
        <template v-else>
          <RouterLink to="/login" class="nav-button">Login</RouterLink>
        </template>
      </div>
      <nav>
        <RouterLink to="/" class="nav-button">Home</RouterLink>
        <RouterLink to="/about" class="nav-button">About</RouterLink>
      </nav>
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

const logout = async () => {
  user.value = null;
  localStorage.removeItem('user');
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
});

onUnmounted(() => {
  // Clean up event listener
  window.removeEventListener('user-updated', handleUserUpdate);
});
</script>
<template>
    <div class="auth-container">
      <h1>Login</h1>
      <form>
        <div class="form-group">
          <label for="email">Email:</label>
          <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
          <label for="password">Password:</label>
          <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn">Login</button>
      </form>
      <p class="register-link">
        Don't have an account? <RouterLink to="/register">Register here</RouterLink>
      </p>
    </div>
  </template>
  
  <script setup>
  import { RouterLink } from 'vue-router'
  import '@/assets/auth.css'
  </script>
  
  <style scoped>
  .register-link {
    margin-top: 15px;
  }
  </style>
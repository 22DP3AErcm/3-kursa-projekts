<template>
    <div class="auth-container">
      <h1>Register</h1>
      <form>
        <div class="form-group">
          <label for="email">Email:</label>
          <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
          <label for="name">Name:</label>
          <input type="name" id="name" name="name" required>
        </div>
        <div class="form-group">
          <label for="telephone">Telephone:</label>
          <input type="tel" id="telephone" name="telephone" required>
        </div>
        <div class="form-group">
          <label for="password">Password:</label>
          <input type="password" id="password" name="password" required>
        </div>
        <div class="form-group">
          <label for="confirm-password">Confirm Password:</label>
          <input type="password" id="confirm-password" name="confirm-password" required>
        </div>
        <button type="submit" class="btn">Register</button>
      </form>
      <p class="login-link">
        Already have an account? <RouterLink to="/login">Login here</RouterLink>
      </p>
    </div>
  </template>
  
  <script setup>
  import { RouterLink } from 'vue-router'
  import '@/assets/auth.css'
  </script>
  
  <style scoped>
  .login-link {
    margin-top: 15px;
  }
  </style>
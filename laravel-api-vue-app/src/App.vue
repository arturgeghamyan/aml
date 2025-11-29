<script setup>
import { RouterLink, RouterView } from "vue-router"
import { useAuthStore } from "./stores/auth"

const authStore = useAuthStore()
</script>

<template>
  <header>
    <nav>
      <RouterLink :to="{ name: 'home' }" class="nav-link">Home</RouterLink>

      <div v-if="authStore.user" class="flex items-center space-x-6">
        <p class="text-sm text-slate-300">
          Welcome back {{ authStore.user.first_name }}
        </p>
        <!-- <RouterLink :to="{ name: 'create' }" class="nav-link">
          New Blog
        </RouterLink> -->
        <RouterLink
          v-if="authStore.user.role === 'seller'"
          :to="{ name: 'product-create' }"
          class="nav-link"
        >
          New Product
        </RouterLink>
        <form @submit.prevent="authStore.logout">
          <button class="nav-link">Logout</button>
        </form>
      </div>

      <div v-else class="space-x-6">
        <RouterLink :to="{ name: 'register' }" class="nav-link">
          Register
        </RouterLink>
        <RouterLink :to="{ name: 'login' }" class="nav-link">
          Login
        </RouterLink>
      </div>
    </nav>
  </header>

  <RouterView />
</template>

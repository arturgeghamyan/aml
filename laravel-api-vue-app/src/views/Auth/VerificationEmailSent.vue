<script setup>
import {onMounted} from "vue"

onMounted(() => {
  fetch(`/api/email/verify-notification`, {
    method: "post",
    headers: {
      authorization: `Bearer ${localStorage.getItem("token")}`,
    },
  })
})
</script>

<template>
  <div>
    <h1 class="title">Email Verification Sent</h1>
    <h3 class="title">We have sent a verification link to your email address. Please check your inbox (and spam folder) to verify your
      email.</h3>
  </div>
</template>

<script setup>
import {ref, onMounted} from 'vue'

const message = ref('Verifying your email...')

const verifyEmail = async () => {
  const urlParams = new URLSearchParams(window.location.search)
  const email = urlParams.get('email')
  const verificationUrl = urlParams.get('verification_url')

  if (verificationUrl) {
    try {
      await fetch(decodeURIComponent(verificationUrl), {
        method: "get",
        headers: {
          authorization: `Bearer ${localStorage.getItem("token")}`,
        },
      })
      message.value = 'Email verified successfully!';
    } catch (error) {
      console.error(error.response.data);
      message.value = 'Failed to verify email. Please try again.'
    }
  } else {
    message.value = 'Invalid verification link.'
  }
};

onMounted(verifyEmail);

</script>
<template>
  <div>
    <h1 class="title">Email Verification</h1>
    <h1 class="title">{{ message }}</h1>
  </div>
</template>


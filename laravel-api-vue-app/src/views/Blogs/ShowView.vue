<script setup>
import {useAuthStore} from "@/stores/auth"
import {useBlogsStore} from "@/stores/blogs"
import {onMounted, ref} from "vue"
import {RouterLink, useRoute} from "vue-router"

const route = useRoute()
const {getBlog, deleteBlog} = useBlogsStore()
const authStore = useAuthStore()
const blog = ref(null)
const apiUrl = import.meta.env.VITE_API_URL

const formatDate = (date) => {
  const options = {year: "numeric", month: "long", day: "numeric"}
  return new Date(date).toLocaleDateString(undefined, options)
}

onMounted(async () => (blog.value = await getBlog(route.params.id)))

</script>


<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
    <article class="space-y-6" v-if="blog">
      <h1 class="text-4xl font-bold text-gray-800">{{ blog.title }}</h1>

      <div class="flex items-center space-x-4 text-gray-500 text-sm">
        <p>By {{ blog.user.name }}</p>
        <span>&bull;</span>
        <p>{{ formatDate(blog.created_at) }}</p>
      </div>

      <div>
        <img
            :src="apiUrl+blog.image"
            alt="Blog cover"
            class="w-full rounded-lg shadow-md"
        />
      </div>

      <div class="prose prose-lg max-w-none text-gray-700">
        <p>
          {{ blog.body }}
        </p>
      </div>

      <div class="mt-6">
        <div class="flex items-center gap-6 mt-6">

          <div v-if="authStore.user && authStore.user.id === blog.user_id" class="flex items-center gap-6">

            <form @submit.prevent="deleteBlog(blog)">
              <button
                  class="text-red-500 font-bold px-2 py-1 border border-red-300"
              >
                Delete
              </button>
            </form>

            <RouterLink
                :to="{ name: 'update', params: { id: blog.id } }"
                class="text-green-500 font-bold px-2 py-1 border border-green-300"
            >Update
            </RouterLink>

          </div>

          <RouterLink
              to="/"
              class="text-blue-500 font-bold px-2 py-1 border border-blue-300"
          >Back
          </RouterLink>
        </div>
      </div>
    </article>
  </div>
</template>

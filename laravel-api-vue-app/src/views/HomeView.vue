<script setup>
import {useBlogsStore} from "@/stores/blogs"
import {onMounted, ref} from "vue"
import {RouterLink} from "vue-router"

const {getAllBlogs} = useBlogsStore()
const blogs = ref([])
const pagination = ref({})
const currentPage = ref(1)
const apiUrl = import.meta.env.VITE_API_URL

const changePage = async (page) => {
  currentPage.value = page
  await fetchBlog(page)
  window.scrollTo({top: 0, behavior: 'smooth'})
};

const fetchBlog = async (page) => {
  const data = await getAllBlogs(page)
  blogs.value = data.data
  pagination.value = {
    current_page: data.current_page,
    last_page: data.last_page,
  };
}

onMounted(async () => await fetchBlog())
</script>

<template>
  <main class="container mx-auto py-10 px-4">
    <h1 class="text-3xl font-bold text-center mb-10">
      {{ blogs.length <= 0 ? 'There are no' : '' }} Blogs
    </h1>

    <div v-if="blogs.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <div
          v-for="blog in blogs"
          :key="blog.id"
          class="bg-white shadow-md rounded-lg overflow-hidden"
      >
        <img
            :src="apiUrl+blog.image"
            :alt="apiUrl+blog.title"
            class="w-full h-48 object-cover"
        />
        <div class="p-4">
          <h2 class="text-lg font-semibold text-gray-800 mb-2">{{ blog.title }}</h2>
          <p class="text-gray-600 text-sm line-clamp-3">{{ blog.body }}</p>
          <RouterLink
              :to="{ name: 'show', params: { id: blog.id } }"
              class="text-blue-500 font-bold underline"
          >Read more...
          </RouterLink>
          <p class="mt-4 text-sm text-gray-500">By {{ blog.user.name }}</p>
        </div>
      </div>
    </div>

    <div class="flex justify-center items-center mt-10 space-x-4">
      <button
          v-for="page in pagination.last_page"
          :key="page"
          :disabled="page === pagination.current_page"
          @click="changePage(page)"
          class="px-4 py-2 bg-gray-200 text-gray-600 rounded hover:bg-gray-300 disabled:opacity-50"
      >
        {{ page }}
      </button>
    </div>
  </main>
</template>

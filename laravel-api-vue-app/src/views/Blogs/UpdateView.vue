<script setup>
import {useAuthStore} from "@/stores/auth"
import {useBlogsStore} from "@/stores/blogs"
import {storeToRefs} from "pinia"
import {onMounted, reactive, ref} from "vue"
import {useRoute, useRouter} from "vue-router"

const router = useRouter()
const route = useRoute()
const {user} = storeToRefs(useAuthStore())
const {errors} = storeToRefs(useBlogsStore())
const {getBlog, updateBlog} = useBlogsStore()
const blog = ref(null)

const formData = reactive({
  title: "",
  body: "",
  image: ""
})

const handleFileChange = (event) => {
  const file = event.target.files[0]
  const reader = new FileReader()
  reader.onloadend = (file) => {
    formData.image = reader.result
  };
  reader.readAsDataURL(file)
};

onMounted(async () => {
  blog.value = await getBlog(route.params.id)

  if (user.value.id !== blog.value.user_id) {
    router.push({name: "home"});
  } else {
    formData.title = blog.value.title
    formData.body = blog.value.body
  }
});
</script>

<template>
  <main>
    <h1 class="title">Update your blog</h1>

    <form
        @submit.prevent="updateBlog(blog, formData)"
        class="w-1/2 mx-auto space-y-6"
    >
      <div>
        <input type="text" placeholder="Blog Title" v-model="formData.title"/>
        <p v-if="errors.title" class="error">{{ errors.title[0] }}</p>
      </div>

      <div>
        <textarea
            rows="6"
            placeholder="Blog Content"
            v-model="formData.body"
        ></textarea>
        <p v-if="errors.body" class="error">{{ errors.body[0] }}</p>
      </div>

      <div>
        <input type="file" @change="handleFileChange" accept="image/*"/>
        <p v-if="errors.image" class="error">{{ errors.image[0] }}</p>
      </div>

      <button class="primary-btn">Update</button>
    </form>
  </main>
</template>

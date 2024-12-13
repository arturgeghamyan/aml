<script setup>
import {useBlogsStore} from "@/stores/blogs"
import {storeToRefs} from "pinia"
import {reactive} from "vue"

const {errors} = storeToRefs(useBlogsStore())
const {createBlog} = useBlogsStore()

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

</script>

<template>
  <main>
    <h1 class="title">Create a new blog</h1>

    <form
        @submit.prevent="createBlog(formData)"
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

      <button class="primary-btn">Create</button>
    </form>
  </main>
</template>

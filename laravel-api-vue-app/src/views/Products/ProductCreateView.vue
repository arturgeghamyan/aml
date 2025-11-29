<script setup>
import { onMounted, reactive, ref } from "vue";
import { storeToRefs } from "pinia";
import { useProductsStore } from "@/stores/products";
import { useAuthStore } from "@/stores/auth";

const productsStore = useProductsStore();
const authStore = useAuthStore();
const { errors } = storeToRefs(productsStore);
const { createProduct, getCategories } = productsStore;
const categories = ref([]);

const formData = reactive({
  product_name: "",
  product_description: "",
  product_price: "",
  product_brand: "",
  category_id: "",
});

const handleSubmit = () => {
  const payload = {
    product_name: formData.product_name,
    product_description: formData.product_description,
    product_price: formData.product_price ? parseFloat(formData.product_price) : null,
    product_brand: formData.product_brand,
    category_id: formData.category_id || null,
  };

  createProduct(payload);
};

onMounted(async () => {
  categories.value = await getCategories();
});
</script>

<template>
  <main class="py-10">
    <div class="max-w-3xl mx-auto bg-white shadow-md rounded-lg p-8">
      <div class="flex items-center justify-between mb-6">
        <div>
          <p class="text-xs uppercase tracking-wide text-slate-500">Products</p>
          <h1 class="text-2xl font-bold text-slate-800">Create a new product</h1>
        </div>
        <p v-if="authStore.user" class="text-sm text-slate-500">
          Signed in as <span class="font-semibold">{{ authStore.user.name }}</span>
        </p>
      </div>

      <form class="space-y-6" @submit.prevent="handleSubmit">
        <div class="p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded">
          New products start as <span class="font-semibold">inactive</span>. An employee must approve them before they
          become visible.
        </div>

        <div>
          <label for="product_name">Product name</label>
          <input
            id="product_name"
            v-model="formData.product_name"
            type="text"
            placeholder="Wireless headphones"
            required
          />
          <p v-if="errors.product_name" class="error">{{ errors.product_name[0] }}</p>
        </div>

        <div>
          <label for="product_description">Description</label>
          <textarea
            id="product_description"
            v-model="formData.product_description"
            rows="4"
            placeholder="Short overview of the product"
            required
          />
          <p v-if="errors.product_description" class="error">{{ errors.product_description[0] }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="product_price">Price</label>
            <input
              id="product_price"
              v-model="formData.product_price"
              type="number"
              step="0.01"
              min="0"
              placeholder="99.99"
              required
            />
            <p v-if="errors.product_price" class="error">{{ errors.product_price[0] }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="product_brand">Brand</label>
            <input
              id="product_brand"
              v-model="formData.product_brand"
              type="text"
              placeholder="Acme Co."
            />
            <p v-if="errors.product_brand" class="error">{{ errors.product_brand[0] }}</p>
          </div>

          <div>
            <label for="category_id">Category (optional)</label>
            <select
              id="category_id"
              v-model="formData.category_id"
              class="block w-full rounded-md border-0 p-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-blue-500 sm:text-sm bg-white"
            >
              <option value="">Select a category</option>
              <option
                v-for="category in categories"
                :key="category.id"
                :value="category.id"
              >
                {{ category.category_title }}
              </option>
            </select>
            <p v-if="errors.category_id" class="error">{{ errors.category_id[0] }}</p>
          </div>
        </div>

        <button class="primary-btn w-full md:w-40">Save product</button>
      </form>
    </div>
  </main>
</template>

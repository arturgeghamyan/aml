<script setup>
import { onMounted, ref, watch } from "vue";
import { RouterLink } from "vue-router";
import { useProductsStore } from "@/stores/products";
import { useAuthStore } from "@/stores/auth";

const { getAllProducts, getCategories } = useProductsStore();
const authStore = useAuthStore();
const products = ref([]);
const pagination = ref({});
const currentPage = ref(1);
const searchTerm = ref("");
const categoryId = ref("");
const priceMin = ref("");
const priceMax = ref("");
const categories = ref([]);

const changePage = async (page) => {
  currentPage.value = page;
  await fetchProducts(page);
  window.scrollTo({ top: 0, behavior: "smooth" });
};

const fetchProducts = async (page = 1) => {
  const data = await getAllProducts({
    page,
    search: searchTerm.value,
    category_id: categoryId.value,
    price_min: priceMin.value,
    price_max: priceMax.value,
  });
  products.value = data.data;
  pagination.value = {
    current_page: data.current_page,
    last_page: data.last_page,
  };
};

watch(() => authStore.user, () => {
  fetchProducts();
});

onMounted(async () => {
  categories.value = await getCategories();
  await fetchProducts();
});
</script>

<template>
  <main class="container mx-auto py-10 px-4">
    <form
      class="max-w-4xl mx-auto mb-8 grid grid-cols-1 md:grid-cols-4 gap-3 items-end"
      @submit.prevent="fetchProducts(1)"
    >
      <div class="md:col-span-2">
        <label class="text-sm text-slate-600 mb-1 block" for="search">Search</label>
        <input
          id="search"
          type="search"
          v-model="searchTerm"
          placeholder="Search products..."
          class="w-full"
        />
      </div>
      <div>
        <label class="text-sm text-slate-600 mb-1 block" for="category">Category</label>
        <select
          id="category"
          v-model="categoryId"
          class="block w-full rounded-md border-0 p-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-blue-500 sm:text-sm bg-white"
        >
          <option value="">All categories</option>
          <option v-for="category in categories" :key="category.id" :value="category.id">
            {{ category.category_title }}
          </option>
        </select>
      </div>
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="text-sm text-slate-600 mb-1 block" for="price_min">Min price</label>
          <input id="price_min" type="number" step="0.01" min="0" v-model="priceMin" />
        </div>
        <div>
          <label class="text-sm text-slate-600 mb-1 block" for="price_max">Max price</label>
          <input id="price_max" type="number" step="0.01" min="0" v-model="priceMax" />
        </div>
      </div>
      <div class="md:col-span-4 flex gap-3">
        <button class="primary-btn w-28">Apply</button>
        <button
          type="button"
          class="px-3 py-2 rounded border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-100"
          @click="
            searchTerm = '';
            categoryId = '';
            priceMin = '';
            priceMax = '';
            fetchProducts(1);
          "
        >
          Reset
        </button>
      </div>
    </form>

    <h1 class="text-3xl font-bold text-center mb-10">
      {{ products.length <= 0 ? "There are no" : "" }} Products
    </h1>

    <div
      v-if="products.length > 0"
      class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"
    >
      <div
        v-for="product in products"
        :key="product.id || product.product_id"
        class="bg-white shadow-md rounded-lg overflow-hidden border border-slate-100"
      >
        <div class="p-6 space-y-3">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">
              {{ product.product_name }}
            </h2>
            <span
              class="px-2 py-1 text-xs font-semibold rounded-full"
              :class="product.product_status === 'active'
                ? 'bg-green-100 text-green-700'
                : 'bg-amber-100 text-amber-700'"
            >
              {{ product.product_status || "N/A" }}
            </span>
          </div>
          <p class="text-gray-600 text-sm line-clamp-3">
            {{ product.product_description || "No description provided." }}
          </p>
          <div class="flex items-center justify-between text-sm text-slate-600">
            <p class="font-semibold text-slate-900">
              ${{ Number(product.product_price || 0).toFixed(2) }}
            </p>
            <p v-if="product.product_brand">Brand: {{ product.product_brand }}</p>
          </div>
          <RouterLink
            :to="{ name: 'product-show', params: { id: product.id || product.product_id } }"
            class="text-blue-500 font-bold underline"
          >
            View product
          </RouterLink>
          <p
            v-if="authStore.user && authStore.user.role === 'employee' && product.product_status !== 'active'"
            class="text-xs text-amber-600"
          >
            Pending approval
          </p>
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

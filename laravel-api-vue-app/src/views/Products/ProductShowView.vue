<script setup>
import { onMounted, ref, computed } from "vue";
import { RouterLink, useRoute } from "vue-router";
import { useProductsStore } from "@/stores/products";
import { useAuthStore } from "@/stores/auth";

const route = useRoute();
const productsStore = useProductsStore();
const authStore = useAuthStore();
const product = ref(null);

const formatMoney = (amount) =>
  new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(
    amount ?? 0
  );

const productStatus = computed(
  () => product.value?.product_status ?? product.value?.status
);

const statusBadge = computed(() => {
  if (!productStatus.value) return "bg-slate-100 text-slate-700";
  return productStatus.value === "active"
    ? "bg-green-100 text-green-700"
    : "bg-amber-100 text-amber-700";
});

const isEmployee = computed(
  () => authStore.user && authStore.user.role === "employee"
);

const approve = async (status) => {
  const updated = await productsStore.setStatus(
    product.value.product_id ?? product.value.id,
    status
  );
  if (updated) {
    product.value = updated;
  }
};

onMounted(async () => {
  product.value = await productsStore.getProduct(route.params.id);
});
</script>

<template>
  <main class="py-10 px-4">
    <div class="max-w-4xl mx-auto">
      <div v-if="product" class="bg-white shadow-md rounded-lg p-8 space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Product</p>
            <h1 class="text-3xl font-bold text-slate-900">
              {{ product.product_name }}
            </h1>
          </div>
          <span
            class="px-3 py-1 rounded-full text-xs font-semibold"
            :class="statusBadge"
          >
            {{ productStatus || "N/A" }}
          </span>
        </div>

        <p class="text-slate-700 whitespace-pre-line">
          {{
            product.product_description ||
            product.description ||
            "No description provided."
          }}
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="p-4 bg-slate-50 rounded-lg">
            <p class="text-sm text-slate-500 uppercase tracking-wide">Price</p>
            <p class="text-2xl font-semibold text-slate-900">
              {{ formatMoney(product.product_price ?? product.price) }}
            </p>
          </div>
          <div class="p-4 bg-slate-50 rounded-lg">
            <p class="text-sm text-slate-500 uppercase tracking-wide">
              Brand
            </p>
            <p class="text-lg font-medium text-slate-900">
              {{ product.product_brand || product.brand || "Not specified" }}
            </p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="p-4 border rounded-lg">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Category</p>
            <p class="text-base text-slate-900">
              {{ product.category?.category_title || product.category_id || "Not set" }}
            </p>
          </div>
          <div class="p-4 border rounded-lg">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Seller</p>
            <p class="text-base text-slate-900">
              {{ product.seller?.seller_company_name || product.user_id || "Not set" }}
            </p>
          </div>
          <div class="p-4 border rounded-lg">
            <p class="text-xs text-slate-500 uppercase tracking-wide">ID</p>
            <p class="text-base text-slate-900">#{{ product.product_id ?? product.id }}</p>
          </div>
        </div>

        <div
          v-if="isEmployee"
          class="flex flex-wrap items-center gap-3 p-4 border rounded-lg bg-slate-50"
        >
          <p class="text-sm font-semibold text-slate-700">
            Employee actions:
          </p>
          <button
            class="px-3 py-1 rounded bg-green-600 text-white text-sm font-semibold hover:bg-green-700 disabled:opacity-50"
            :disabled="productStatus === 'active'"
            @click="approve('active')"
          >
            Set Active
          </button>
          <button
            class="px-3 py-1 rounded bg-amber-500 text-white text-sm font-semibold hover:bg-amber-600 disabled:opacity-50"
            :disabled="productStatus === 'inactive'"
            @click="approve('inactive')"
          >
            Set Inactive
          </button>
        </div>

        <RouterLink
          to="/"
          class="inline-flex items-center text-blue-500 font-semibold underline"
        >
          ← Back home
        </RouterLink>
      </div>

      <div
        v-else
        class="bg-white shadow-md rounded-lg p-8 text-center text-slate-600"
      >
        Loading product...
      </div>
    </div>
  </main>
</template>

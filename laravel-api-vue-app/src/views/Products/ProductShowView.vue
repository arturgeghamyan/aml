<script setup>
import { onMounted, ref, computed } from "vue";
import { RouterLink, useRoute } from "vue-router";
import { useProductsStore } from "@/stores/products";
import { useAuthStore } from "@/stores/auth";
import { useOrdersStore } from "@/stores/orders";
import { useReviewsStore } from "@/stores/reviews";
import { useReturnsStore } from "@/stores/returns";
import placeholderImage from "@/assets/product-placeholder.jpg";

const route = useRoute();
const productsStore = useProductsStore();
const authStore = useAuthStore();
const ordersStore = useOrdersStore();
const reviewsStore = useReviewsStore();
const returnsStore = useReturnsStore();
const product = ref(null);
const quantity = ref(1);
const ordering = ref(false);
const orderMessage = ref("");
const paymentMessage = ref("");
const reviews = ref([]);
const topProducts = ref([]);
const allProducts = ref([]);

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

const placeOrder = async () => {
  if (!product.value) return;
  ordering.value = true;
  orderMessage.value = "";
  const res = await ordersStore.createOrder({
    payment_method: "Credit Card",
    items: [
      {
        product_id: product.value.product_id ?? product.value.id,
        quantity: quantity.value,
      },
    ],
  });
  ordering.value = false;
  if (res) {
    orderMessage.value = `Order placed! #${res.order?.order_id ?? ""}`;
    paymentMessage.value = res.order?.payment_transaction?.payment?.payment_status || "";
  }
};

const payNow = async () => {
  if (!ordersStore.lastOrder?.order_id) return;
  ordering.value = true;
  const res = await ordersStore.payOrder(ordersStore.lastOrder.order_id);
  ordering.value = false;
  if (res) {
    paymentMessage.value = res.order?.payment_transaction?.payment?.payment_status || "paid";
    orderMessage.value = res.message || orderMessage.value;
  }
};

onMounted(async () => {
  product.value = await productsStore.getProduct(route.params.id);
  const [best, all] = await Promise.all([
    productsStore.getBestSellers(3),
    productsStore.getAllProducts({ paginate: 9 }),
  ]);
  topProducts.value = Array.isArray(best) ? best : [];
  allProducts.value = (all.data || []).filter((p) => {
    const pid = p.id ?? p.product_id;
    const currentId = product.value?.product_id ?? product.value?.id;
    return pid !== currentId;
  });
  if (product.value) {
    reviews.value = await reviewsStore.getReviewsByProduct(
      product.value.product_id ?? product.value.id
    );
  }
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

        <img
          :src="placeholderImage"
          alt="Product preview"
          class="w-full h-72 object-cover rounded-lg border border-slate-200"
        />

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
          v-if="authStore.user && authStore.user.role === 'customer'"
          class="flex flex-wrap items-center gap-3 p-4 border rounded-lg bg-slate-50"
        >
          <div class="flex items-center gap-2">
            <label for="qty" class="text-sm text-slate-600">Quantity</label>
            <input
              id="qty"
              type="number"
              min="1"
              v-model.number="quantity"
              class="w-20"
            />
          </div>
          <button
            class="px-4 py-2 rounded bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 disabled:opacity-50"
            :disabled="ordering"
            @click="placeOrder"
          >
            {{ ordering ? "Placing..." : "Place Order" }}
          </button>
          <p v-if="ordersStore.errors?.message" class="text-sm text-red-500">
            {{ Array.isArray(ordersStore.errors.message) ? ordersStore.errors.message[0] : ordersStore.errors.message }}
          </p>
          <p v-if="orderMessage" class="text-sm text-green-600 font-semibold">
            {{ orderMessage }}
          </p>
          <div v-if="ordersStore.lastOrder && paymentMessage !== 'paid'" class="flex items-center gap-3">
            <button
              class="px-4 py-2 rounded bg-amber-500 text-white text-sm font-semibold hover:bg-amber-600 disabled:opacity-50"
              :disabled="ordering"
              @click="payNow"
            >
              {{ ordering ? "Processing..." : "Fake Pay" }}
            </button>
            <p class="text-sm text-slate-600">
              Status: {{ paymentMessage || "pending" }}
            </p>
          </div>
          <p v-else-if="paymentMessage === 'paid'" class="text-sm text-green-600 font-semibold">
            Payment status: paid
          </p>
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

        <section class="mt-8">
          <h2 class="text-xl font-semibold text-slate-900 mb-3">Reviews</h2>
          <div v-if="reviews.length === 0" class="text-slate-500 text-sm">
            No reviews yet.
          </div>
          <div v-else class="space-y-4">
            <div
              v-for="review in reviews"
              :key="review.review_id"
              class="border border-slate-100 rounded-lg p-4 bg-slate-50"
            >
              <div class="flex items-center justify-between mb-2">
                <p class="font-semibold text-slate-800">
                  {{ review.title || "Review" }}
                </p>
                <span class="text-amber-600 text-sm font-semibold">
                  {{ Number(review.review_rating).toFixed(1) }} / 5
                </span>
              </div>
              <p class="text-slate-700 text-sm whitespace-pre-line">
                {{ review.comment || "No comment." }}
              </p>
              <p class="text-xs text-slate-500 mt-2">
                By {{ review.user?.first_name || "Customer" }}
              </p>
            </div>
          </div>
        </section>
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

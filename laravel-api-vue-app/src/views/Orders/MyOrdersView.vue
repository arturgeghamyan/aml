<script setup>
import { onMounted, ref, computed } from "vue";
import { useOrdersStore } from "@/stores/orders";
import { useAuthStore } from "@/stores/auth";
import { useReviewsStore } from "@/stores/reviews";
import { useReturnsStore } from "@/stores/returns";

const ordersStore = useOrdersStore();
const authStore = useAuthStore();
const reviewsStore = useReviewsStore();
const returnsStore = useReturnsStore();
const orders = ref([]);
const loading = ref(false);
const paying = ref(null);
const submitting = ref(null);
const newReview = ref({});
const newReturnReason = ref({});

const formatMoney = (amount) =>
  new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(
    amount ?? 0
  );

const totalForOrder = (order) =>
  order.items?.reduce(
    (sum, item) => sum + Number(item.quantity) * Number(item.unit_price),
    0
  ) ?? 0;

const paymentStatus = (order) =>
  order.payment_transaction?.payment?.payment_status || "pending";

const fetchOrders = async () => {
  loading.value = true;
  const data = await ordersStore.getMyOrders();
  if (Array.isArray(data)) {
    orders.value = data;
  }
  loading.value = false;
};

const payOrder = async (order) => {
  paying.value = order.order_id;
  const res = await ordersStore.payOrder(order.order_id);
  if (res?.order) {
    const idx = orders.value.findIndex((o) => o.order_id === order.order_id);
    if (idx !== -1) orders.value[idx] = res.order;
  }
  paying.value = null;
};

const returnKey = (orderId, itemId) => `${orderId}-${itemId}`;

const draftKey = (orderId, itemId) => `${orderId}-${itemId}`;

const ensureDraft = (orderId, itemId) => {
  const key = draftKey(orderId, itemId);
  if (!newReview.value[key]) {
    newReview.value[key] = { review_rating: 5, title: "", comment: "" };
  }
  return newReview.value[key];
};

const submitReview = async (order, item) => {
  submitting.value = `${order.order_id}-${item.order_item_id}`;
  const draft = ensureDraft(order.order_id, item.order_item_id);
  const payload = {
    order_id: order.order_id,
    order_item_id: item.order_item_id,
    review_rating: draft.review_rating ?? 5,
    title: draft.title || "",
    comment: draft.comment || "",
  };
  const res = await reviewsStore.createReview(payload);
  if (res) {
    const idx = orders.value.findIndex((o) => o.order_id === order.order_id);
    if (idx !== -1) {
      const itemIdx = orders.value[idx].items.findIndex(
        (i) => i.order_item_id === item.order_item_id
      );
      if (itemIdx !== -1) {
        orders.value[idx].items[itemIdx].review = res;
      }
    }
    newReview.value[draftKey(order.order_id, item.order_item_id)] = {};
  }
  submitting.value = null;
};

const requestReturn = async (order, item) => {
  const key = returnKey(order.order_id, item.order_item_id);
  const payload = {
    order_id: order.order_id,
    order_item_id: item.order_item_id,
    reason: newReturnReason.value[key] || "",
  };
  const res = await returnsStore.createReturnRequest(payload);
  if (res) {
    const idx = orders.value.findIndex((o) => o.order_id === order.order_id);
    if (idx !== -1) {
      const itemIdx = orders.value[idx].items.findIndex(
        (i) => i.order_item_id === item.order_item_id
      );
      if (itemIdx !== -1) {
        orders.value[idx].items[itemIdx].return_request = res;
      }
    }
    newReturnReason.value[key] = "";
  }
};

const userName = computed(() => authStore.user?.name || "Customer");

onMounted(fetchOrders);
</script>

<template>
  <main class="max-w-5xl mx-auto py-10 px-4 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs uppercase text-slate-500 tracking-wide">Orders</p>
        <h1 class="text-2xl font-bold text-slate-800">Your orders</h1>
      </div>
      <p class="text-sm text-slate-500">Signed in as {{ userName }}</p>
    </div>

    <div
      v-if="loading"
      class="bg-white border border-slate-200 rounded-lg p-6 text-center text-slate-500"
    >
      Loading orders...
    </div>

    <div v-else-if="orders.length === 0" class="text-center text-slate-500">
      You have no orders yet.
    </div>

    <div class="space-y-4" v-else>
      <div
        v-for="order in orders"
        :key="order.order_id"
        class="bg-white border border-slate-200 rounded-lg shadow-sm"
      >
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-600">Order #{{ order.order_id }}</p>
            <p class="text-xs text-slate-500">
              {{ new Date(order.order_date).toLocaleString() }}
            </p>
          </div>
          <div class="flex items-center gap-2">
            <span
              class="px-3 py-1 rounded-full text-xs font-semibold"
              :class="paymentStatus(order) === 'paid'
                ? 'bg-green-100 text-green-700'
                : 'bg-amber-100 text-amber-700'"
            >
              {{ paymentStatus(order) }}
            </span>
            <span class="text-sm font-semibold text-slate-800">
              {{ formatMoney(totalForOrder(order)) }}
            </span>
            <button
              v-if="paymentStatus(order) !== 'paid'"
              class="px-3 py-1.5 rounded bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 disabled:opacity-50"
              :disabled="paying === order.order_id"
              @click="payOrder(order)"
            >
              {{ paying === order.order_id ? "Paying..." : "Pay now" }}
            </button>
          </div>
        </div>
        <div class="p-4 space-y-3">
          <div
            v-for="item in order.items"
            :key="item.order_item_id"
            class="flex flex-col gap-2 border border-slate-100 rounded-md px-3 py-2"
          >
            <div class="flex items-center justify-between">
              <div>
                <p class="font-semibold text-slate-800">
                  {{ item.product?.product_name || "Product" }}
                </p>
                <p class="text-xs text-slate-500">Qty: {{ item.quantity }}</p>
              </div>
              <p class="text-sm text-slate-700">
                {{ formatMoney(item.unit_price) }}
              </p>
            </div>

            <div v-if="paymentStatus(order) === 'paid'">
              <div v-if="item.review" class="text-sm text-slate-700">
                <p class="font-semibold">Your review</p>
                <p class="text-amber-600">Rating: {{ item.review.review_rating }}</p>
                <p class="font-medium">{{ item.review.title }}</p>
                <p class="text-slate-600 whitespace-pre-line">{{ item.review.comment }}</p>
              </div>
              <form
                v-else
                class="space-y-2 bg-slate-50 p-3 rounded-md"
                @submit.prevent="submitReview(order, item)"
              >
                <div class="flex items-center gap-2">
                  <label class="text-sm text-slate-600">Rating</label>
                  <input
                    type="number"
                    min="1"
                    max="5"
                    step="0.1"
                    v-model.number="ensureDraft(order.order_id, item.order_item_id).review_rating"
                    class="w-20"
                    placeholder="5"
                  />
                </div>
                <input
                  type="text"
                  v-model="ensureDraft(order.order_id, item.order_item_id).title"
                  placeholder="Title (optional)"
                />
                <textarea
                  rows="2"
                  v-model="ensureDraft(order.order_id, item.order_item_id).comment"
                  placeholder="Write your thoughts"
                ></textarea>
                <button
                  class="px-3 py-1.5 rounded bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 disabled:opacity-50"
                  :disabled="submitting === `${order.order_id}-${item.order_item_id}`"
                >
                  {{ submitting === `${order.order_id}-${item.order_item_id}` ? "Saving..." : "Submit review" }}
                </button>
                <p v-if="reviewsStore.errors?.message" class="text-xs text-red-500">
                  {{ Array.isArray(reviewsStore.errors.message) ? reviewsStore.errors.message[0] : reviewsStore.errors.message }}
                </p>
              </form>

              <div class="mt-3 bg-slate-50 p-3 rounded-md">
                <div v-if="item.return_request">
                  <p class="text-sm font-semibold text-slate-800">
                    Return status: {{ item.return_request.request_status }}
                  </p>
                  <p v-if="item.return_request.refund" class="text-sm text-green-700">
                    Refund: ${{ Number(item.return_request.refund.amount || 0).toFixed(2) }}
                  </p>
                </div>
                <form
                  v-else
                  class="space-y-2"
                  @submit.prevent="requestReturn(order, item)"
                >
                  <label class="text-sm text-slate-600">Request a return</label>
                  <textarea
                    rows="2"
                    v-model="newReturnReason[returnKey(order.order_id, item.order_item_id)]"
                    placeholder="Reason (optional)"
                  ></textarea>
                  <button
                    class="px-3 py-1.5 rounded bg-amber-500 text-white text-xs font-semibold hover:bg-amber-600"
                  >
                    Submit return request
                  </button>
                  <p v-if="returnsStore.errors?.message" class="text-xs text-red-500">
                    {{ Array.isArray(returnsStore.errors.message) ? returnsStore.errors.message[0] : returnsStore.errors.message }}
                  </p>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</template>

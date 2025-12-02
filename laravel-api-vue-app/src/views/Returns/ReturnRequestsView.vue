<script setup>
import { onMounted, ref } from "vue";
import { useReturnsStore } from "@/stores/returns";
import { useAuthStore } from "@/stores/auth";

const returnsStore = useReturnsStore();
const authStore = useAuthStore();
const requests = ref([]);
const loading = ref(false);
const processing = ref(null);
const decisions = ref({});

const fetchRequests = async () => {
  loading.value = true;
  const data = await returnsStore.getAllReturnRequests();
  if (Array.isArray(data)) {
    requests.value = data;
  }
  loading.value = false;
};

const decide = async (request, status) => {
  processing.value = request.return_request_id;
  const decision = decisions.value[request.return_request_id] || {};
  const res = await returnsStore.decideReturnRequest(request.return_request_id, {
    request_status: status,
    amount: decision.amount,
    reason: decision.reason,
  });
  if (res) {
    const idx = requests.value.findIndex(
      (r) => r.return_request_id === request.return_request_id
    );
    if (idx !== -1) {
      requests.value[idx] = res;
    }
  }
  processing.value = null;
};

onMounted(fetchRequests);
</script>

<template>
  <main class="max-w-5xl mx-auto py-10 px-4 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs uppercase text-slate-500 tracking-wide">Returns</p>
        <h1 class="text-2xl font-bold text-slate-800">Return Requests</h1>
      </div>
      <p class="text-sm text-slate-500">
        Signed in as {{ authStore.user?.name || "Employee" }}
      </p>
    </div>

    <div
      v-if="loading"
      class="bg-white border border-slate-200 rounded-lg p-6 text-center text-slate-500"
    >
      Loading requests...
    </div>

    <div v-else-if="requests.length === 0" class="text-center text-slate-500">
      No return requests found.
    </div>

    <div class="space-y-4" v-else>
      <div
        v-for="req in requests"
        :key="req.return_request_id"
        class="bg-white border border-slate-200 rounded-lg shadow-sm p-4 space-y-2"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="font-semibold text-slate-800">
              #{{ req.return_request_id }} - {{ req.order_item?.product?.product_name || "Item" }}
            </p>
            <p class="text-xs text-slate-500">Order {{ req.order_id }} | Item {{ req.order_item_id }}</p>
          </div>
          <span
            class="px-3 py-1 rounded-full text-xs font-semibold"
            :class="req.request_status === 'accepted'
              ? 'bg-green-100 text-green-700'
              : req.request_status === 'rejected'
                ? 'bg-red-100 text-red-700'
                : 'bg-amber-100 text-amber-700'"
          >
            {{ req.request_status }}
          </span>
        </div>

        <p class="text-sm text-slate-700 whitespace-pre-line" v-if="req.reason">
          Reason: {{ req.reason }}
        </p>
        <p class="text-sm text-green-700" v-if="req.refund">
          Refund: ${{ Number(req.refund.amount || 0).toFixed(2) }}
        </p>

        <div v-if="req.request_status === 'pending'" class="space-y-2">
          <input
            type="number"
            step="0.01"
            min="0"
            v-model="(decisions[req.return_request_id] ||= {}).amount"
            placeholder="Refund amount (optional)"
          />
          <textarea
            rows="2"
            v-model="(decisions[req.return_request_id] ||= {}).reason"
            placeholder="Decision note (optional)"
          ></textarea>
          <div class="flex gap-2">
            <button
              class="px-3 py-1.5 rounded bg-green-600 text-white text-xs font-semibold hover:bg-green-700 disabled:opacity-50"
              :disabled="processing === req.return_request_id"
              @click="decide(req, 'accepted')"
            >
              {{ processing === req.return_request_id ? 'Saving...' : 'Accept' }}
            </button>
            <button
              class="px-3 py-1.5 rounded bg-red-500 text-white text-xs font-semibold hover:bg-red-600 disabled:opacity-50"
              :disabled="processing === req.return_request_id"
              @click="decide(req, 'rejected')"
            >
              {{ processing === req.return_request_id ? 'Saving...' : 'Reject' }}
            </button>
          </div>
          <p v-if="returnsStore.errors?.message" class="text-xs text-red-500">
            {{ Array.isArray(returnsStore.errors.message) ? returnsStore.errors.message[0] : returnsStore.errors.message }}
          </p>
        </div>
      </div>
    </div>
  </main>
</template>

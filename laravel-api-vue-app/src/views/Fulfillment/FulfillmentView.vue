<script setup>
import { onMounted, ref } from "vue";
import { useOrdersStore } from "@/stores/orders";

const ordersStore = useOrdersStore();
const orders = ref([]);
const warehouses = ref([]);
const loading = ref(false);
const saving = ref(null);
const orderSelections = ref({});
const stockEdits = ref({});

const fetchData = async () => {
  loading.value = true;
  const [orderData, warehouseData] = await Promise.all([
    ordersStore.getEmployeeOrders(),
    fetch("/api/warehouses").then((r) => r.json()),
  ]);
  if (Array.isArray(orderData)) {
    orders.value = orderData;
    orderData.forEach((order) => {
      const firstWh = order.items?.find((i) => i.warehouse_id)?.warehouse_id;
      orderSelections.value[order.order_id] = firstWh || "";
    });
  }
  warehouses.value = warehouseData.warehouses || [];
  warehouses.value.forEach((wh) => {
    stockEdits.value[wh.warehouse_id] = wh.stock_amount;
  });
  loading.value = false;
};

const saveAssignment = async (order) => {
  saving.value = order.order_id;
  const warehouse_id = orderSelections.value[order.order_id];
  if (!warehouse_id) {
    saving.value = null;
    return;
  }

  const updated = await ordersStore.assignWarehouses(order.order_id, warehouse_id);
  if (updated) {
    await fetchData();
  }
  saving.value = null;
};

onMounted(fetchData);
</script>

<template>
  <main class="max-w-5xl mx-auto py-10 px-4 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs uppercase text-slate-500 tracking-wide">Fulfillment</p>
        <h1 class="text-2xl font-bold text-slate-800">Assign Warehouses</h1>
      </div>
    </div>

    <div
      v-if="loading"
      class="bg-white border border-slate-200 rounded-lg p-6 text-center text-slate-500"
    >
      Loading orders...
    </div>

    <div v-else-if="orders.length === 0" class="text-center text-slate-500">
      No orders available.
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
            <p class="text-sm font-semibold text-slate-800">
              {{ order.payment_transaction?.payment?.payment_status || "pending" }}
            </p>
            <select
              v-model="orderSelections[order.order_id]"
              class="block w-44 rounded-md border-0 p-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-blue-500 sm:text-sm bg-white"
            >
              <option value="">Select warehouse</option>
              <option
                v-for="wh in warehouses"
                :key="wh.warehouse_id"
                :value="wh.warehouse_id"
              >
                {{ wh.warehouse_name }}
              </option>
            </select>
          </div>
        </div>
        <div class="p-4 space-y-3">
          <div
            v-for="item in order.items"
            :key="item.order_item_id"
            class="flex items-center justify-between border border-slate-100 rounded-md px-3 py-2 gap-4"
          >
            <div>
              <p class="font-semibold text-slate-800">
                {{ item.product?.product_name || "Product" }}
              </p>
              <p class="text-xs text-slate-500">Qty: {{ item.quantity }}</p>
            </div>
            <p class="text-sm text-slate-700">
              Assigned: {{ item.warehouse?.warehouse_name || "Not set" }}
            </p>
          </div>
          <button
            class="px-3 py-1.5 rounded bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 disabled:opacity-50"
            :disabled="saving === order.order_id"
            @click="saveAssignment(order)"
          >
            {{ saving === order.order_id ? "Saving..." : "Save assignment" }}
          </button>
          <p v-if="ordersStore.errors?.message" class="text-xs text-red-500">
            {{ Array.isArray(ordersStore.errors.message) ? ordersStore.errors.message[0] : ordersStore.errors.message }}
          </p>
        </div>
      </div>
    </div>

    <section class="bg-white border border-slate-200 rounded-lg shadow-sm p-4 space-y-3">
      <h2 class="text-lg font-semibold text-slate-800">Adjust Stock</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div
          v-for="wh in warehouses"
          :key="wh.warehouse_id"
          class="border border-slate-100 rounded-md p-3 flex items-center justify-between"
        >
          <div>
            <p class="font-semibold text-slate-800">{{ wh.warehouse_name }}</p>
            <p class="text-xs text-slate-500">Current: {{ wh.stock_amount }}</p>
          </div>
          <div class="flex items-center gap-2">
            <input
              type="number"
              min="0"
              v-model.number="stockEdits[wh.warehouse_id]"
              class="w-24"
            />
            <button
              class="px-3 py-1.5 rounded bg-green-600 text-white text-xs font-semibold hover:bg-green-700"
              @click="async () => {
                await ordersStore.updateWarehouseStock(wh.warehouse_id, stockEdits[wh.warehouse_id]);
                fetchData();
              }"
            >
              Save
            </button>
          </div>
        </div>
      </div>
    </section>
  </main>
</template>

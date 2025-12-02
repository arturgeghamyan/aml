<script setup>
import { onMounted, ref } from "vue";
import { useAuthStore } from "@/stores/auth";

const authStore = useAuthStore();
const stats = ref(null);
const loading = ref(false);
const errors = ref(null);

const fetchStats = async () => {
  loading.value = true;
  errors.value = null;
  const res = await fetch("/api/stats/employee", {
    headers: {
      Authorization: `Bearer ${localStorage.getItem("token")}`,
    },
  });
  const data = await res.json();
  if (res.ok) {
    stats.value = data;
  } else {
    errors.value = data;
  }
  loading.value = false;
};

onMounted(fetchStats);
</script>

<template>
  <main class="max-w-6xl mx-auto py-10 px-4 space-y-8">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs uppercase text-slate-500 tracking-wide">Analytics</p>
        <h1 class="text-2xl font-bold text-slate-800">Employee Stats</h1>
      </div>
      <p class="text-sm text-slate-500">
        Signed in as {{ authStore.user?.name || "Employee" }}
      </p>
    </div>

    <div v-if="loading" class="text-center text-slate-500">Loading...</div>
    <div v-else-if="errors" class="text-center text-red-500">Error loading stats.</div>
    <div v-else-if="stats" class="space-y-8">
      <section>
        <h2 class="text-lg font-semibold text-slate-800 mb-2">Top Selling Products</h2>
        <table>
          <thead>
            <tr>
              <th>Product</th>
              <th>Units Sold</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in stats.best_selling" :key="row.product_id">
              <td>{{ row.product_name }}</td>
              <td>{{ row.total_units_sold }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section>
        <h2 class="text-lg font-semibold text-slate-800 mb-2">Monthly Revenue</h2>
        <table>
          <thead>
            <tr>
              <th>Year</th>
              <th>Month</th>
              <th>Total Revenue</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in stats.monthly_revenue" :key="`${row.year}-${row.month}`">
              <td>{{ row.year }}</td>
              <td>{{ row.month }}</td>
              <td>{{ row.total_revenue }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section>
        <h2 class="text-lg font-semibold text-slate-800 mb-2">Top Sellers</h2>
        <table>
          <thead>
            <tr>
              <th>Seller</th>
              <th>Total Sales</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in stats.top_sellers" :key="row.user_id">
              <td>{{ row.seller_company_name || row.user_id }}</td>
              <td>{{ row.total_sales }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section>
        <h2 class="text-lg font-semibold text-slate-800 mb-2">Top Rated Products</h2>
        <table>
          <thead>
            <tr>
              <th>Product</th>
              <th>Avg Rating</th>
              <th>Reviews</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in stats.top_rated_products" :key="row.product_id">
              <td>{{ row.product_name }}</td>
              <td>{{ row.avg_rating }}</td>
              <td>{{ row.reviews_count }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section>
        <h2 class="text-lg font-semibold text-slate-800 mb-2">Recent Price Points</h2>
        <table>
          <thead>
            <tr>
              <th>Product</th>
              <th>Price</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, idx) in stats.price_fluctuation" :key="`${row.product_id}-${idx}`">
              <td>{{ row.product_name }}</td>
              <td>{{ row.recent_sold_price }}</td>
              <td>{{ row.order_date }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section>
        <h2 class="text-lg font-semibold text-slate-800 mb-2">Customer Summary</h2>
        <table>
          <thead>
            <tr>
              <th>Customer</th>
              <th>Orders</th>
              <th>Spent</th>
              <th>Reviews</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in stats.customer_summary" :key="row.customer_id">
              <td>{{ row.customer_name || row.customer_id }}</td>
              <td>{{ row.total_orders }}</td>
              <td>{{ row.total_spent }}</td>
              <td>{{ row.total_reviews_written }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section>
        <h2 class="text-lg font-semibold text-slate-800 mb-2">Low Stock</h2>
        <table>
          <thead>
            <tr>
              <th>Warehouse</th>
              <th>Stock</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in stats.low_stock" :key="row.warehouse_name">
              <td>{{ row.warehouse_name }}</td>
              <td>{{ row.total_stock }}</td>
              <td>{{ row.stock_status }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section>
        <h2 class="text-lg font-semibold text-slate-800 mb-2">Delivery Report</h2>
        <table>
          <thead>
            <tr>
              <th>Order</th>
              <th>Warehouse</th>
              <th>Status</th>
              <th>Delivered At</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in stats.delivery_report" :key="row.order_id">
              <td>{{ row.order_id }}</td>
              <td>{{ row.warehouse_name }}</td>
              <td>{{ row.delivery_status }}</td>
              <td>{{ row.delivered_at }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section>
        <h2 class="text-lg font-semibold text-slate-800 mb-2">Refund Analysis</h2>
        <table>
          <thead>
            <tr>
              <th>Reason</th>
              <th>Total Refunds</th>
              <th>Amount</th>
              <th>Products</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, idx) in stats.refund_analysis" :key="idx">
              <td>{{ row.return_reason }}</td>
              <td>{{ row.total_refunds }}</td>
              <td>{{ row.total_refund_amount }}</td>
              <td>{{ row.affected_products }}</td>
            </tr>
          </tbody>
        </table>
      </section>
    </div>
  </main>
</template>

import { defineStore } from "pinia";

export const useOrdersStore = defineStore("ordersStore", {
  state: () => ({
    errors: {},
    lastOrder: null,
  }),
  actions: {
    async getEmployeeOrders() {
      const res = await fetch("/api/orders/fulfillment", {
        headers: {
          Authorization: `Bearer ${localStorage.getItem("token")}`,
        },
      });
      const data = await res.json();
      if (res.ok && !data.errors) {
        this.errors = {};
        return data;
      }
      this.errors = data.errors || { message: data.message || ["Unable to fetch orders"] };
      return [];
    },

    async assignWarehouses(orderId, warehouse_id) {
      const res = await fetch(`/api/orders/${orderId}/assign-warehouses`, {
        method: "post",
        headers: {
          Authorization: `Bearer ${localStorage.getItem("token")}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ warehouse_id }),
      });
      const data = await res.json();
      if (res.ok && !data.errors) {
        this.errors = {};
        return data.order ?? data;
      }
      this.errors = data.errors || { message: data.message || ["Unable to assign warehouses"] };
      return null;
    },

    async updateWarehouseStock(warehouseId, stock_amount) {
      const res = await fetch(`/api/warehouses/${warehouseId}/stock`, {
        method: "post",
        headers: {
          Authorization: `Bearer ${localStorage.getItem("token")}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ stock_amount }),
      });
      const data = await res.json();
      if (res.ok && !data.errors) {
        this.errors = {};
        return data.warehouse ?? data;
      }
      this.errors = data.errors || { message: data.message || ["Unable to update stock"] };
      return null;
    },
    async getMyOrders() {
      const res = await fetch("/api/orders", {
        headers: {
          Authorization: `Bearer ${localStorage.getItem("token")}`,
        },
      });

      const data = await res.json();
      if (res.ok && !data.errors) {
        return data;
      }

      this.errors = data.errors || { message: data.message || ["Unable to fetch orders"] };
      return [];
    },

    async createOrder(payload) {
      const res = await fetch("/api/orders", {
        method: "post",
        headers: {
          Authorization: `Bearer ${localStorage.getItem("token")}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      });

      const data = await res.json();

      if (res.ok && !data.errors) {
        this.errors = {};
        this.lastOrder = data.order ?? data;
        return data;
      }

      this.errors = data.errors || { message: data.message || ["Unable to place order"] };
      return null;
    },

    async payOrder(orderId, payment_method = "Credit Card") {
      const res = await fetch(`/api/orders/${orderId}/pay`, {
        method: "post",
        headers: {
          Authorization: `Bearer ${localStorage.getItem("token")}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ payment_method }),
      });

      const data = await res.json();

      if (res.ok && !data.errors) {
        this.errors = {};
        if (data.order) this.lastOrder = data.order;
        return data;
      }

      this.errors = data.errors || { message: data.message || ["Unable to pay"] };
      return null;
    },
  },
});

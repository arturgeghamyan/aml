import { defineStore } from "pinia";

export const useReturnsStore = defineStore("returnsStore", {
  state: () => ({
    errors: {},
  }),
  actions: {
    async createReturnRequest(payload) {
      const res = await fetch("/api/return-requests", {
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
        return data.return_request ?? data;
      }

      this.errors = data.errors || { message: data.message || ["Unable to request return"] };
      return null;
    },

    async getAllReturnRequests() {
      const res = await fetch("/api/return-requests", {
        headers: {
          Authorization: `Bearer ${localStorage.getItem("token")}`,
        },
      });
      const data = await res.json();
      if (res.ok && !data.errors) {
        this.errors = {};
        return data;
      }
      this.errors = data.errors || { message: data.message || ["Unable to fetch return requests"] };
      return [];
    },

    async decideReturnRequest(id, payload) {
      const res = await fetch(`/api/return-requests/${id}/decide`, {
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
        return data.return_request ?? data;
      }

      this.errors = data.errors || { message: data.message || ["Unable to process request"] };
      return null;
    },
  },
});

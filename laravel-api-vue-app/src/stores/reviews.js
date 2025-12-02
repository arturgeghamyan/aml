import { defineStore } from "pinia";

export const useReviewsStore = defineStore("reviewsStore", {
  state: () => ({
    errors: {},
  }),
  actions: {
    async getReviewsByProduct(productId) {
      const res = await fetch(`/api/products/${productId}/reviews`);
      const data = await res.json();

      if (res.ok && !data.errors) {
        this.errors = {};
        return data.reviews ?? [];
      }

      this.errors = data.errors || { message: data.message || ["Unable to fetch reviews"] };
      return [];
    },

    async createReview(payload) {
      const res = await fetch("/api/reviews", {
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
        return data.review ?? data;
      }

      this.errors = data.errors || { message: data.message || ["Unable to submit review"] };
      return null;
    },
  },
});

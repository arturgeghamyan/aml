import { defineStore } from "pinia";

export const useProductsStore = defineStore("productsStore", {
  state: () => ({
    errors: {},
    product: null,
  }),
  actions: {
    async getCategories() {
      const res = await fetch("/api/categories");
      const data = await res.json();
      return data.categories ?? [];
    },

    async setStatus(productId, status) {
      const res = await fetch(`/api/products/${productId}`, {
        method: "put",
        headers: {
          Authorization: `Bearer ${localStorage.getItem("token")}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ product_status: status }),
      });

      const data = await res.json();
      if (!res.ok || data.errors) {
        this.errors = data.errors || { status: ["Unable to update status"] };
        return null;
      }

      this.errors = {};
      this.product = data.product ?? this.product;
      return data.product;
    },

    async getAllProducts({
      page = 1,
      paginate = 12,
      search = "",
      category_id = "",
      price_min = "",
      price_max = "",
    } = {}) {
      const headers = {};
      const token = localStorage.getItem("token");
      if (token) {
        headers.Authorization = `Bearer ${token}`;
      }

      const params = new URLSearchParams({
        page,
        paginate,
      });

      if (search) params.append("search", search);
      if (category_id) params.append("category_id", category_id);
      if (price_min !== "" && price_min !== null) params.append("price_min", price_min);
      if (price_max !== "" && price_max !== null) params.append("price_max", price_max);

      const res = await fetch(`/api/products?${params.toString()}`, { headers });
      const data = await res.json();
      return data;
    },

    async getProduct(id) {
      const res = await fetch(`/api/products/${id}`);
      const data = await res.json();
      const product = data.product ?? data;
      this.product = product;
      return product;
    },

    async createProduct(formData) {
      const res = await fetch("/api/products", {
        method: "post",
        headers: {
          Authorization: `Bearer ${localStorage.getItem("token")}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify(formData),
      });

      const data = await res.json();

      if (data.errors) {
        this.errors = data.errors;
        return null;
      }

      this.errors = {};
      const product = data.product ?? data;

      if (product?.id) {
        this.router.push({ name: "product-show", params: { id: product.id } });
      } else {
        this.router.push({ name: "home" });
      }

      return product;
    },
  },
});

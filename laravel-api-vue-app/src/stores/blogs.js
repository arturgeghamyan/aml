import { defineStore } from "pinia";
import { useAuthStore } from "./auth";

export const useBlogsStore = defineStore("blogsStore", {
  state: () => {
    return {
      errors: {},
    };
  },
  actions: {
    async getAllBlogs(page = 1, paginate = 9) {
      const res = await fetch(`/api/blogs?page=${page}&paginate=${paginate}`);
      const data = await res.json();


      return data;
    },
    async getBlog(blog) {
      const res = await fetch(`/api/blogs/${blog}`);
      const data = await res.json();

      return data.blog;
    },
    async createBlog(formData) {
      const res = await fetch("/api/blogs", {
        method: "post",
        headers: {
          Authorization: `Bearer ${localStorage.getItem("token")}`,
        },
        body: JSON.stringify(formData),
      });

      const data = await res.json();

      if (data.errors) {
        this.errors = data.errors;
      } else {
        this.router.push({ name: "home" });
        this.errors = {}
      }
    },
    /******************* Delete a blog *******************/
    async deleteBlog(blog) {
      const authStore = useAuthStore();
      if (authStore.user.id === blog.user_id) {
        const res = await fetch(`/api/blogs/${blog.id}`, {
          method: "delete",
          headers: {
            Authorization: `Bearer ${localStorage.getItem("token")}`,
          },
        });

        const data = await res.json();
        if (res.ok) {
          this.router.push({ name: "home" });
        }
        console.log(data);
      }
    },
    /******************* Update a blog *******************/
    async updateBlog(blog, formData) {
      const authStore = useAuthStore();
      if (authStore.user.id === blog.user_id) {
        const res = await fetch(`/api/blogs/${blog.id}`, {
          method: "put",
          headers: {
            Authorization: `Bearer ${localStorage.getItem("token")}`,
          },
          body: JSON.stringify(formData),
        });

        const data = await res.json();
        if (data.errors) {
          this.errors = data.errors;
        } else {
          this.router.push({ name: "home" });
          this.errors = {}
        }
      }
    },
  },
});

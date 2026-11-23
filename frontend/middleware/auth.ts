export default defineNuxtRouteMiddleware((to) => {
  if (import.meta.server) return;
  const authStore = useAuthStore();

  if (!authStore.isLoggedIn) {
    return navigateTo('/login?redirect=' + encodeURIComponent(to.fullPath));
  }
});

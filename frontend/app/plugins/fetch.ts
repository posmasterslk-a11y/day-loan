export default defineNuxtPlugin((nuxtApp) => {
  const { token } = useAuth()
  
  globalThis.$fetch = $fetch.create({
    onRequest({ request, options }) {
      if (token.value) {
        options.headers = options.headers || {}
        // @ts-ignore
        options.headers.Authorization = `Bearer ${token.value}`
      }
    },
    onResponseError({ response }) {
      if (response.status === 401) {
        const { logout } = useAuth()
        logout()
        navigateTo('/login')
      }
    }
  })
})

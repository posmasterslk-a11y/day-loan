export default defineNuxtPlugin((nuxtApp) => {
  const { token } = useAuth()
  
  globalThis.$fetch = $fetch.create({
    onRequest({ request, options }) {
      options.headers = new Headers(options.headers || {})
      options.headers.set('Accept', 'application/json')
      if (token.value) {
        options.headers.set('Authorization', `Bearer ${token.value}`)
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

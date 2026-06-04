export const useApi = () => {
  const { token, logout } = useAuth()
  
  return $fetch.create({
    onRequest({ request, options }) {
      options.headers = new Headers(options.headers || {})
      options.headers.set('Accept', 'application/json')
      if (token.value) {
        options.headers.set('Authorization', `Bearer ${token.value}`)
      }
    },
    onResponseError({ response }) {
      if (response.status === 401) {
        logout()
        navigateTo('/login')
      }
    }
  })
}

export const useApi = () => {
  const { token, logout } = useAuth()
  
  return $fetch.create({
    onRequest({ request, options }) {
      if (token.value) {
        options.headers = options.headers || {}
        // @ts-ignore
        options.headers.Authorization = `Bearer ${token.value}`
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

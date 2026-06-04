import { useFetch, useRuntimeConfig, navigateTo } from '#app'
import { useAuth } from './useAuth'

export const useApiFetch: typeof useFetch = (request, opts?) => {
  const { token, logout } = useAuth()
  
  return useFetch(request, {
    ...opts,
    onRequest({ request, options }) {
      options.headers = new Headers(options.headers || {})
      options.headers.set('Accept', 'application/json')
      if (token.value) {
        options.headers.set('Authorization', `Bearer ${token.value}`)
      }
      if (opts?.onRequest) {
        // @ts-ignore
        opts.onRequest({ request, options })
      }
    },
    onResponseError({ response, options }) {
      if (response.status === 401) {
        logout()
        navigateTo('/login')
      }
      if (opts?.onResponseError) {
        // @ts-ignore
        opts.onResponseError({ response, options })
      }
    }
  })
}

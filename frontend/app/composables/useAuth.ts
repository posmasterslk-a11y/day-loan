export const useAuth = () => {
  const token = useCookie('auth_token', { maxAge: 60 * 60 * 24 * 7, path: '/' }) // 7 days
  const user = useState<any>('auth_user', () => null)
  
  const isAuthenticated = computed(() => !!token.value)
  
  const setToken = (newToken: string) => {
    token.value = newToken
  }

  const setUser = (newUser: any) => {
    user.value = newUser
  }

  const logout = () => {
    token.value = null
    user.value = null
  }

  return {
    token,
    user,
    isAuthenticated,
    setToken,
    setUser,
    logout
  }
}

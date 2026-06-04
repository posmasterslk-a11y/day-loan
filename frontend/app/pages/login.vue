<script setup lang="ts">
definePageMeta({
  layout: 'auth'
})

const config = useRuntimeConfig()
const { setToken, setUser } = useAuth()
const router = useRouter()

const form = reactive({
  email: '',
  password: ''
})

const loading = ref(false)
const errorMsg = ref('')

const handleLogin = async () => {
  loading.value = true
  errorMsg.value = ''
  
  try {
    const response = await $fetch<any>(`${config.public.apiBase}/login`, {
      method: 'POST',
      body: form
    })
    
    setToken(response.access_token)
    setUser(response.user)
    
    // Redirect to home
    router.push('/')
  } catch (e: any) {
    if (e.response && e.response.status === 422) {
      errorMsg.value = e.response._data.message || 'Invalid credentials'
    } else {
      errorMsg.value = 'Failed to login. Please try again.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="animate-in fade-in slide-in-from-bottom-4 duration-700">
    <div class="mb-10">
      <div class="inline-flex items-center justify-center p-3 bg-primary/10 rounded-2xl mb-5">
        <UIcon name="i-lucide-landmark" class="w-8 h-8 text-primary" />
      </div>
      <h2 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">
        Welcome back
      </h2>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        Sign in to access your administrative dashboard.
      </p>
    </div>

    <form @submit.prevent="handleLogin" class="space-y-6 w-full">
      <UAlert v-if="errorMsg" :title="errorMsg" color="error" variant="soft" icon="i-lucide-alert-circle" />

      <UFormGroup label="Email Address" name="email">
        <UInput v-model="form.email" type="email" placeholder="admin@example.com" icon="i-lucide-mail" size="lg" class="w-full mt-1" :ui="{ wrapper: 'w-full' }" required />
      </UFormGroup>

      <UFormGroup label="Password" name="password">
        <template #hint>
          <a href="#" class="text-xs font-semibold text-primary hover:text-primary-600 transition-colors">Forgot password?</a>
        </template>
        <UInput v-model="form.password" type="password" placeholder="••••••••" icon="i-lucide-lock" size="lg" class="w-full mt-1" :ui="{ wrapper: 'w-full' }" required />
      </UFormGroup>

      <div class="flex items-center justify-between">
         <UCheckbox name="remember" label="Remember me" color="primary" />
      </div>

      <UButton type="submit" color="primary" block size="lg" :loading="loading" class="w-full mt-8 py-3 text-base font-semibold shadow-lg hover:shadow-primary/30 hover:-translate-y-0.5 transition-all duration-300">
        Sign In
      </UButton>
    </form>
    
    <div class="mt-12 text-center text-xs text-gray-400 font-medium">
       &copy; 2026 Microfinance Admin. All rights reserved.
    </div>
  </div>
</template>

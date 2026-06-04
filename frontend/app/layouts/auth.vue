<template>
  <div class="min-h-screen flex bg-white dark:bg-gray-900">
    <!-- Left side: Login form -->
    <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24 relative z-10 bg-white dark:bg-gray-900 shadow-[20px_0_40px_-15px_rgba(0,0,0,0.1)]">
      <div class="mx-auto w-full max-w-sm lg:w-96">
        <NuxtPage />
      </div>
    </div>
    
    <!-- Right side: Beautiful gradient/image -->
    <div class="hidden lg:block relative w-0 flex-1">
      <div class="absolute inset-0 h-full w-full bg-gradient-to-br from-primary-600 via-primary-800 to-gray-900 overflow-hidden">
         <!-- Abstract shapes or patterns -->
         <div class="absolute -top-32 -left-32 w-[500px] h-[500px] rounded-full bg-white opacity-5 blur-3xl"></div>
         <div class="absolute bottom-0 right-0 w-[600px] h-[600px] rounded-full bg-primary-400 opacity-20 blur-[100px]"></div>
         <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] rounded-full border border-white/5 shadow-[0_0_100px_rgba(255,255,255,0.05)]"></div>
         
         <!-- Glassmorphism card -->
         <div class="flex flex-col justify-center items-center h-full text-white px-12 text-center relative z-10">
            <div class="backdrop-blur-md bg-white/10 border border-white/20 p-10 rounded-3xl shadow-2xl max-w-lg transform hover:scale-105 transition-transform duration-500">
              <UIcon name="i-lucide-chart-pie" class="w-20 h-20 mb-6 text-primary-200" />
              <h1 class="text-4xl font-extrabold tracking-tight mb-4 text-white">Microfinance Hub</h1>
              <p class="text-lg text-primary-100/80 leading-relaxed">
                Advanced collection management, ledger analytics, and customer tracking in one beautiful, streamlined dashboard.
              </p>
            </div>
         </div>
      </div>
    </div>
  </div>
</template>

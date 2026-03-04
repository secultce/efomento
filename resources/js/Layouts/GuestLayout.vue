<script setup>
import GuestHeader from '@/Components/GuestAppHeader.vue'
</script>

<template>
  <div class="min-h-screen bg-[#f5f5f5] relative">
    
    <GuestHeader />

    <div class="flex min-h-[calc(100vh-64px)] relative">

      <!-- Triangle (hidden on small screens automatically) -->
      <div
        class="hidden md:block absolute bottom-0 right-0
               w-full h-full bg-white
               [clip-path:polygon(100%_100%,35%_100%,100%_0)]"
      ></div>

      <!-- Content -->
      <main
        class="flex flex-1 items-center justify-center
               px-6 sm:px-10 md:px-16 lg:px-24
               relative z-10"
      >
        <slot />
      </main>

    </div>
  </div>
</template>
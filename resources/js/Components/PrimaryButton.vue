<template>
    <button
        v-bind="$attrs"
        class="inline-flex bg-[#ffcc05FF] items-center justify-center rounded-md  px-4 py-2 text-xs font-semibold tracking-widest transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-gray-800 focus:ring-offset-2 !text-black"
    >
        <slot />
    </button>
</template>
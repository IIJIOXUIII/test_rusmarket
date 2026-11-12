<script setup>
// Заглушка главной — по макету основной экран «График».
</script>

<template>
    <div class="flex min-h-screen flex-1 items-center justify-center bg-[#F5F5F7]">
        <div class="text-center">
            <h1 class="text-3xl font-semibold tracking-tight text-[#101115]">Главная</h1>
            <p class="mt-2 text-[15px] text-[#9AA0AA]">Раздел в разработке — см. «Смены»</p>
            <router-link to="/shifts" class="mt-4 inline-block rounded-xl bg-[#4F7FFF] px-5 py-2.5 text-[15px] font-semibold text-white">К графику смен</router-link>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const collapsed = ref(false);

const items = [
    { name: 'Главная', icon: 'home', route: '/' },
    { name: 'Смены', icon: 'calendar', route: '/shifts' },
];
</script>

<template>
    <aside class="flex h-full w-[290px] flex-shrink-0 flex-col bg-white" :class="{ 'w-[84px]': collapsed }">
        <div class="flex items-center justify-between px-6 pt-6 pb-2">
            <span v-if="!collapsed" class="text-xl font-semibold tracking-tight text-[#101115]">CRM</span>
            <button class="rounded-lg p-2 text-[#707176] hover:bg-gray-100" aria-label="Уведомления">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9" />
                    <path d="M13.7 21a2 2 0 0 1-3.4 0" />
                </svg>
            </button>
        </div>
        <nav class="mt-4 flex flex-col gap-1 px-3">
            <router-link v-for="it in items" :key="it.name" :to="it.route"
                class="flex items-center gap-3 rounded-xl px-3 py-3 text-[15px] font-medium text-[#101115] hover:bg-gray-50"
                active-class="bg-[#F2F4F7]">
                <svg v-if="it.icon === 'home'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9 22 9 12 15 12 15 22" />
                </svg>
                <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" />
                    <line x1="16" y1="2" x2="16" y2="6" />
                    <line x1="8" y1="2" x2="8" y2="6" />
                    <line x1="3" y1="10" x2="21" y2="10" />
                </svg>
                <span v-if="!collapsed">{{ it.name }}</span>
            </router-link>
        </nav>
        <div class="mt-auto px-3 pb-6">
            <button class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-[15px] font-medium text-[#101115] hover:bg-gray-50" @click="collapsed = !collapsed">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" />
                    <line x1="9" y1="3" x2="9" y2="21" />
                </svg>
                <span v-if="!collapsed">Свернуть меню</span>
            </button>
        </div>
    </aside>
</template>

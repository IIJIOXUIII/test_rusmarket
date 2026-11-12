<script setup>
import Sidebar from '../components/Sidebar.vue';
</script>

<template>
    <div class="flex min-h-screen">
        <Sidebar />
        <router-view />
    </div>
</template>

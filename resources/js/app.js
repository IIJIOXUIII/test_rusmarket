import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './components/App.vue';
import HomePage from './pages/HomePage.vue';
import SchedulePage from './components/SchedulePage.vue';

// База /test/ — деплой за edge-прокси по под-пути.
const router = createRouter({
    history: createWebHistory('/test/'),
    routes: [
        { path: '/', name: 'home', component: HomePage },
        { path: '/shifts', name: 'shifts', component: SchedulePage },
    ],
});

createApp(App).use(router).mount('#app');

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    templates: { type: Array, default: () => [] },
    hasSelection: { type: Boolean, default: false },
});

const emit = defineEmits(['apply', 'apply-custom']);

// Счётчики применений шаблонов (как в макете — бейдж с числом справа)
const counts = ref({});

const customStart = ref('09:00');
const customEnd = ref('17:00');

function hours(tpl) {
    const [sh, sm] = tpl.start_time.split(':').map(Number);
    let [eh, em] = tpl.end_time.split(':').map(Number);
    if (eh <= sh) eh += 24;
    return Math.round((eh * 60 + em - sh * 60 - sm) / 60);
}

function apply(tpl) {
    counts.value[tpl.id] = (counts.value[tpl.id] || 0) + 1;
    emit('apply', tpl);
}

function applySelected() {
    const tpl = props.templates[0];
    if (tpl) apply(tpl);
}

defineExpose({ applySelected });

const accent = {
    'Утро': '#6A46E8',
    'День': '#4F7FFF',
    'Вечер': '#0F9B84',
};
</script>

<template>
    <aside class="w-[400px] flex-shrink-0 rounded-2xl bg-white p-5 shadow-sm">
        <h2 class="text-[15px] font-medium text-[#101115]">Шаблоны смен</h2>

        <div class="mt-3 flex flex-col gap-2.5">
            <button
                v-for="tpl in templates"
                :key="tpl.id"
                class="flex items-center justify-between rounded-xl border border-gray-200 bg-[#F2F4F7] px-4 py-3 text-left hover:border-blue-300"
                @click="apply(tpl)">
                <span>
                    <span class="block text-[15px] font-semibold" :style="{ color: accent[tpl.name] || '#101115' }">{{ tpl.name }}</span>
                    <span class="block text-sm text-[#9AA0AA]">{{ tpl.start_time.slice(0, 5) }} - {{ tpl.end_time.slice(0, 5) }} · {{ hours(tpl) }} ч</span>
                </span>
                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-white text-xs font-semibold text-[#101115]">
                    {{ counts[tpl.id] || 0 }}
                </span>
            </button>
        </div>

        <h2 class="mt-6 text-[15px] font-medium text-[#101115]">Своя смена</h2>
        <div class="mt-2 flex gap-3">
            <label class="flex-1">
                <span class="mb-1 block text-xs text-[#9AA0AA]">Начало</span>
                <input v-model="customStart" type="time" class="w-full rounded-xl bg-[#F2F4F7] px-4 py-2.5 text-[15px] text-[#101115] focus:outline-none focus:ring-2 focus:ring-blue-200" />
            </label>
            <label class="flex-1">
                <span class="mb-1 block text-xs text-[#9AA0AA]">Конец</span>
                <input v-model="customEnd" type="time" class="w-full rounded-xl bg-[#F2F4F7] px-4 py-2.5 text-[15px] text-[#101115] focus:outline-none focus:ring-2 focus:ring-blue-200" />
            </label>
        </div>

        <button
            class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-[#EAF1FE] px-4 py-3 text-[15px] font-semibold text-[#4F7FFF] hover:bg-blue-100 disabled:opacity-50"
            :disabled="!hasSelection"
            @click="emit('apply-custom', customStart, customEnd)">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Поставить в выделенные
        </button>
    </aside>
</template>

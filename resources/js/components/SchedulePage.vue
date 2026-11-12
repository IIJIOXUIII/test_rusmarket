<script setup>
import { ref, computed, onMounted } from 'vue';
import TemplatePanel from './TemplatePanel.vue';

// ---- Данные ----
const loading = ref(true);
const employees = ref([]);
const templates = ref([]);
const shifts = ref({}); // ключ `${empId}|${date}` -> {start_time, end_time}
const weekStart = ref('');
const saving = ref(false);
const savedAt = ref(null);
const search = ref('');
const view = ref('week'); // 'week' | 'month' (макет: неделя активна; месяц — переключатель по ТЗ)

const DAYS = ['пн', 'вт', 'ср', 'чт', 'пт', 'сб', 'вс'];

const weekDays = computed(() => {
    if (!weekStart.value) return [];
    const start = new Date(weekStart.value + 'T00:00:00');
    return Array.from({ length: 7 }, (_, i) => {
        const d = new Date(start);
        d.setDate(start.getDate() + i);
        return {
            date: fmt(d),
            label: `${DAYS[i]} ${d.getDate()}`,
        };
    });
});

const monthDays = computed(() => {
    if (!weekStart.value) return [];
    const base = new Date(weekStart.value + 'T00:00:00');
    const first = new Date(base.getFullYear(), base.getMonth(), 1);
    const startMonday = new Date(first);
    startMonday.setDate(first.getDate() - ((first.getDay() + 6) % 7));
    const res = [];
    for (let i = 0; i < 42; i++) {
        const d = new Date(startMonday);
        d.setDate(startMonday.getDate() + i);
        res.push({ date: fmt(d), label: String(d.getDate()), dim: d.getMonth() !== base.getMonth() });
    }
    return res;
});

const gridDays = computed(() => (view.value === 'week' ? weekDays.value : monthDays.value));

function fmt(d) {
    const p = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`;
}

const filteredEmployees = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return employees.value;
    return employees.value.filter((e) => e.name.toLowerCase().includes(q));
});

// ---- Выделение ----
const selection = ref(new Set()); // ключи `${empId}|${date}`
let dragging = ref(false);

function key(empId, date) {
    return `${empId}|${date}`;
}

function cellClick(emp, date) {
    toggleCell(key(emp.id, date));
}

function toggleCell(k) {
    const s = new Set(selection.value);
    if (s.has(k)) s.delete(k);
    else s.add(k);
    selection.value = s;
}

function hasShift(empId, date) {
    return !!shifts.value[key(empId, date)];
}

function isSelected(empId, date) {
    return selection.value.has(key(empId, date));
}

function applyTemplateToSelection(tpl) {
    const s = { ...shifts.value };
    for (const k of selection.value) {
        const [empId, date] = k.split('|');
        s[k] = { start_time: tpl.start_time.slice(0, 5), end_time: tpl.end_time.slice(0, 5) };
    }
    shifts.value = s;
}

function customShiftToSelection(start, end) {
    const s = { ...shifts.value };
    for (const k of selection.value) {
        s[k] = { start_time: start.slice(0, 5), end_time: end.slice(0, 5) };
    }
    shifts.value = s;
}

function clearSelection() {
    const s = { ...shifts.value };
    for (const k of selection.value) delete s[k];
    shifts.value = s;
}

function dropSelection() {
    selection.value = new Set();
}

function selectedStats() {
    const emps = new Set();
    const dates = new Set();
    for (const k of selection.value) {
        const [e, d] = k.split('|');
        emps.add(e);
        dates.add(d);
    }
    return { cells: selection.value.size, emps: emps.size, dates: dates.size };
}

const selStats = computed(selectedStats);

// ---- Покрытие ----
function coverage(date) {
    return employees.value.filter((e) => hasShift(e.id, date)).length;
}

function shiftHours(shift) {
    const [sh, sm] = shift.start_time.split(':').map(Number);
    let [eh, em] = shift.end_time.split(':').map(Number);
    if (eh <= sh) eh += 24;
    return (eh * 60 + em - sh * 60 - sm) / 60;
}

function employeeHours(emp) {
    let h = 0;
    for (const d of gridDays.value) {
        const sh = shifts.value[key(emp.id, d.date)];
        if (sh) h += shiftHours(sh);
    }
    return h;
}

function shiftLabel(empId, date) {
    const sh = shifts.value[key(empId, date)];
    if (!sh) return null;
    const short = (t) => t.slice(0, 5);
    return {
        top: `${short(sh.start_time)} - ${short(sh.end_time)}`,
        bottom: `${Math.round(shiftHours(sh))} ч`,
        custom: sh.start_time !== '07:00:00' && sh.start_time !== '10:00:00' && sh.start_time !== '15:00:00',
        evening: sh.start_time.slice(0, 5) === '15:00',
    };
}

// ---- Загрузка/сохранение ----
async function loadWeek(date) {
    const r = await fetch(`api/schedule?week=${date}`);
    const j = await r.json();
    const map = {};
    for (const sh of j.shifts) {
        map[key(sh.employee_id, sh.date.slice(0, 10))] = {
            start_time: sh.start_time.slice(0, 8),
            end_time: sh.end_time.slice(0, 8),
        };
    }
    shifts.value = map;
    weekStart.value = j.weekStart;
}

onMounted(async () => {
    try {
        const b = await fetch('api/bootstrap').then((r) => r.json());
        employees.value = b.employees;
        templates.value = b.templates;
        weekStart.value = b.weekStart;
        await loadWeek(b.weekStart);
    } catch (e) {
        console.error('bootstrap failed', e);
    }
    loading.value = false;
});

async function save() {
    saving.value = true;
    const list = Object.entries(shifts.value).map(([k, v]) => {
        const [empId, date] = k.split('|');
        return { employee_id: Number(empId), date, start_time: v.start_time.slice(0, 5), end_time: v.end_time.slice(0, 5) };
    });
    await fetch('api/schedule', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ weekStart: weekStart.value, shifts: list }),
    });
    savedAt.value = new Date();
    saving.value = false;
}

async function publish() {
    await save();
}

function prevWeek() {
    const d = new Date(weekStart.value + 'T00:00:00');
    const delta = view.value === 'week' ? -7 : -28;
    d.setDate(d.getDate() + delta);
    loadWeek(fmt(d));
}

function nextWeek() {
    const d = new Date(weekStart.value + 'T00:00:00');
    const delta = view.value === 'week' ? 7 : 28;
    d.setDate(d.getDate() + delta);
    loadWeek(fmt(d));
}
</script>

<template>
    <div class="flex h-screen min-h-0 flex-1 flex-col overflow-hidden bg-[#F5F5F7]">
        <!-- Верхняя строка -->
        <header class="flex items-center gap-4 px-8 pt-6">
            <button class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-[#101115] shadow-sm" aria-label="Назад">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6" />
                </svg>
            </button>
            <span class="text-[17px] font-medium text-[#101115]">Графики смен</span>
        </header>

        <div class="flex items-end justify-between px-8 pt-6">
            <h1 class="text-4xl font-semibold tracking-tight text-[#101115]">График</h1>
            <div class="flex gap-3">
                <button class="rounded-xl bg-white px-5 py-2.5 text-[15px] font-semibold text-[#4F7FFF] shadow-sm hover:bg-blue-50">
                    Опубликовать
                </button>
                <button
                    class="rounded-xl bg-[#4F7FFF] px-5 py-2.5 text-[15px] font-semibold text-white shadow-sm hover:bg-blue-600 disabled:opacity-60"
                    :disabled="saving"
                    @click="save">
                    {{ saving ? 'Сохранение…' : 'Сохранить' }}
                </button>
            </div>
        </div>

        <div class="flex min-h-0 flex-1 gap-5 px-8 pt-4 pb-8">
            <!-- Основная сетка -->
            <section class="flex min-h-0 flex-1 flex-col rounded-2xl bg-white p-4 shadow-sm">
                <!-- Тулбар выделения -->
                <div v-if="selStats.cells > 0" class="mb-3 flex items-center justify-between rounded-xl bg-[#1E2129] px-4 py-2.5 text-white">
                    <div class="flex items-center gap-2 text-sm">
                        <span>Выделено:</span>
                        <span class="rounded-md bg-white/15 px-2 py-0.5 text-xs">{{ selStats.cells }} ячеек</span>
                        <span class="text-white/80">{{ selStats.emps }} сотрудника(-ов) · {{ selStats.dates }} дн.</span>
                    </div>
                    <div class="flex items-center gap-5 text-sm">
                        <button class="flex items-center gap-1.5 hover:text-blue-300" @click="$refs.tplPanel.applySelected()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                            Применить шаблон
                        </button>
                        <button class="flex items-center gap-1.5 hover:text-red-300" @click="clearSelection">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
                            Очистить
                        </button>
                        <button class="flex items-center gap-1.5 hover:text-red-300" @click="dropSelection">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            Снять
                        </button>
                    </div>
                </div>

                <!-- Поиск + переключатель -->
                <div class="mb-3 flex items-center gap-4">
                    <label class="relative flex-1">
                        <svg class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.5" y2="16.5"/></svg>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Поиск по ФИО"
                            class="w-full rounded-xl bg-[#F2F4F7] py-2.5 pl-11 pr-4 text-[15px] text-[#101115] placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-200" />
                    </label>
                    <div class="flex rounded-xl bg-[#F2F4F7] p-1">
                        <button class="rounded-lg px-6 py-2 text-[15px] font-medium" :class="view === 'month' ? 'bg-[#1E2129] text-white' : 'text-[#101115]'" @click="view = 'month'">Месяц</button>
                        <button class="rounded-lg px-6 py-2 text-[15px] font-medium" :class="view === 'week' ? 'bg-[#1E2129] text-white' : 'text-[#101115]'" @click="view = 'week'">Неделя</button>
                    </div>
                    <div class="flex gap-1">
                        <button class="rounded-lg px-3 py-2 text-[#101115] hover:bg-gray-100" @click="prevWeek">‹</button>
                        <button class="rounded-lg px-3 py-2 text-[#101115] hover:bg-gray-100" @click="nextWeek">›</button>
                    </div>
                </div>

                <!-- Таблица -->
                <div class="flex min-h-0 flex-1 flex-col overflow-y-auto">
                    <div
                        class="grid min-w-[720px] h-full"
                        :style="{
                            gridTemplateColumns: `220px repeat(${gridDays.length}, minmax(96px, 1fr))`,
                            gridTemplateRows: `48px repeat(${filteredEmployees.length || 1}, minmax(72px, 1fr)) 44px`,
                        }">
                        <!-- заголовок -->
                        <div class="py-3 pr-4 text-left text-[15px] text-[#9AA0AA]">Сотрудник</div>
                        <div v-for="d in gridDays" :key="'h' + d.date" class="py-3 text-center text-[15px] text-[#9AA0AA]">
                            {{ view === 'week' ? d.label : d.label }}
                        </div>

                        <!-- строки -->
                        <template v-for="emp in filteredEmployees" :key="emp.id">
                            <div class="flex items-center gap-3 border-t border-gray-100 py-2 pr-4">
                                <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full text-sm font-semibold text-white" :style="{ background: emp.avatar_color }">                                    {{ emp.name.split(' ').map(w => w[0]).join('') }}
                                </span>
                                <span>
                                    <span class="block text-[15px] font-medium text-[#101115]">{{ emp.name }}</span>
                                    <span class="block text-xs text-[#9AA0AA]">{{ emp.position }} · {{ employeeHours(emp) }} ч</span>
                                </span>
                            </div>
                            <div
                                v-for="d in gridDays"
                                :key="emp.id + d.date"
                                class="border-t border-gray-100 p-1"
                                @mousedown="dragging = true; cellClick(emp, d.date)"
                                @mouseenter="if (dragging && !hasShift(emp.id, d.date)) { if (!isSelected(emp.id, d.date)) toggleCell(key(emp.id, d.date)); }"
                                @mouseup="dragging = false"
                                @mouseleave="dragging = false">
                                <div
                                    class="flex h-full min-h-[72px] cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed border-gray-200"
                                    :class="[
                                        isSelected(emp.id, d.date) && !hasShift(emp.id, d.date) ? 'border-solid border-blue-300 bg-[#EEF3FF]' : '',
                                        hasShift(emp.id, d.date) ? 'border-solid' : '',
                                    ]"
                                    :style="hasShift(emp.id, d.date) ? (shiftLabel(emp.id, d.date).custom ? { background: '#DCF7F0', borderColor: '#9FE8DA' } : (shiftLabel(emp.id, d.date).evening ? { background: '#DFF3FF', borderColor: '#A8DCF7' } : { background: '#E7DFFB', borderColor: '#C9B8F2' })) : {}">
                                    <template v-if="hasShift(emp.id, d.date)">
                                        <span class="text-[15px] font-medium" :style="{ color: shiftLabel(emp.id, d.date).custom ? '#0F9B84' : '#6A46E8' }">{{ shiftLabel(emp.id, d.date).top }}</span>
                                        <span class="text-xs" :style="{ color: shiftLabel(emp.id, d.date).custom ? '#0F9B84' : '#6A46E8' }">{{ shiftLabel(emp.id, d.date).bottom }}</span>
                                    </template>
                                    <template v-else-if="isSelected(emp.id, d.date)">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4F7FFF" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- покрытие -->
                        <div class="mt-1 border-t border-gray-100 py-3 pr-4 text-[15px] text-[#9AA0AA]">Покрытие</div>
                        <div
                            v-for="d in gridDays"
                            :key="'c' + d.date"
                            class="mt-1 flex items-center justify-center rounded-lg py-2 text-[15px] font-medium"
                            :class="coverage(d.date) === 0 ? 'bg-[#FBE3E3] text-[#D93838]' : 'bg-[#EAF1FE] text-[#4F7FFF]'">
                            {{ coverage(d.date) }}
                        </div>
                    </div>
                </div>
            </section>

            <!-- Правая панель -->
            <TemplatePanel
                ref="tplPanel"
                :templates="templates"
                :has-selection="selStats.cells > 0"
                @apply="applyTemplateToSelection"
                @apply-custom="customShiftToSelection" />
        </div>
    </div>
</template>

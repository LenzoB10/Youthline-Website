<script setup>
import { formatMoney } from '@/Helpers/money';
import { useThemeStore } from '@/Stores/theme';
import { BarChart } from 'echarts/charts';
import { GridComponent, TooltipComponent } from 'echarts/components';
import * as echarts from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

echarts.use([BarChart, GridComponent, TooltipComponent, CanvasRenderer]);

const props = defineProps({
    months: { type: Array, required: true },
    currency: { type: String, default: 'MWK' },
});

const theme = useThemeStore();
const view = ref('chart');
const el = ref(null);
let chart = null;

/* Brand-led categorical palette (Youthline green + gold first), validated for colour-blind
   separation in both modes. Grey is kept for the grid and text, never for a series. */
const SERIES = [
    { key: 'fees', label: 'School fees', light: '#1c7d3c', dark: '#207c3b' },
    { key: 'basic_needs', label: 'Basic needs', light: '#e0a800', dark: '#b88a00' },
    { key: 'family_support', label: 'Family support', light: '#2a78d6', dark: '#3987e5' },
    { key: 'businesses', label: 'Businesses', light: '#eb6834', dark: '#d95926' },
];

const money = (v) => formatMoney(v, props.currency);
const colour = (s) => (theme.darkMode ? s.dark : s.light);
const totals = computed(() => Object.fromEntries(SERIES.map((s) => [s.key, props.months.reduce((sum, m) => sum + (m[s.key] ?? 0), 0)])));
const monthTotal = (m) => SERIES.reduce((sum, s) => sum + (m[s.key] ?? 0), 0);

const compact = (v) => {
    if (v >= 1_000_000) return `${+(v / 1_000_000).toFixed(1)}M`;
    if (v >= 1_000) return `${+(v / 1_000).toFixed(0)}K`;
    return `${v}`;
};

function options() {
    const dark = theme.darkMode;
    const surface = dark ? '#16191d' : '#ffffff';
    const ink = dark ? '#aaaaaa' : '#606060';
    const grid = dark ? 'rgba(255,255,255,0.08)' : '#eef0f2';

    // The data-end of each stack gets the rounded corner, so find each month's top non-zero series.
    const topKey = props.months.map((m) => [...SERIES].reverse().find((s) => (m[s.key] ?? 0) > 0)?.key);

    return {
        animationDuration: 600,
        grid: { left: 8, right: 8, top: 12, bottom: 4, containLabel: true },
        tooltip: {
            trigger: 'axis',
            axisPointer: { type: 'shadow', shadowStyle: { color: dark ? 'rgba(255,255,255,0.04)' : 'rgba(36,48,57,0.05)' } },
            backgroundColor: dark ? '#1b1f24' : '#ffffff',
            extraCssText: 'border-radius: 10px; box-shadow: 0 12px 32px -8px rgba(16,24,40,0.16);',
            borderColor: dark ? '#3a3a3a' : '#e5e7eb',
            textStyle: { color: dark ? '#f3f4f6' : '#0f0f0f', fontSize: 12 },
            formatter: (items) => {
                const m = props.months[items[0].dataIndex];
                const rows = SERIES.map(
                    (s) =>
                        `<div style="display:flex;justify-content:space-between;gap:16px;align-items:center">
                            <span><span style="display:inline-block;width:8px;height:8px;border-radius:2px;margin-right:6px;background:${colour(s)}"></span>${s.label}</span>
                            <span style="font-weight:600">${money(m[s.key] ?? 0)}</span></div>`,
                ).join('');
                return `<div style="font-weight:600;margin-bottom:4px">${m.label}</div>${rows}
                    <div style="display:flex;justify-content:space-between;gap:16px;border-top:1px solid ${grid};margin-top:4px;padding-top:4px"><span>Total</span><span style="font-weight:700">${money(monthTotal(m))}</span></div>`;
            },
        },
        xAxis: {
            type: 'category',
            data: props.months.map((m) => m.label),
            axisLine: { lineStyle: { color: grid } },
            axisTick: { show: false },
            axisLabel: { color: ink, fontSize: 11 },
        },
        yAxis: {
            type: 'value',
            splitLine: { lineStyle: { color: grid } },
            axisLabel: { color: ink, fontSize: 11, formatter: compact },
        },
        series: SERIES.map((s) => ({
            name: s.label,
            type: 'bar',
            stack: 'support',
            barMaxWidth: 28,
            emphasis: { focus: 'series' },
            data: props.months.map((m, i) => ({
                value: m[s.key] ?? 0,
                itemStyle: {
                    color: colour(s),
                    borderColor: surface,
                    borderWidth: 1,
                    borderRadius: topKey[i] === s.key ? [4, 4, 0, 0] : 0,
                },
            })),
        })),
    };
}

function render() {
    if (!el.value) return;
    chart ??= echarts.init(el.value);
    chart.setOption(options(), true);
}

const resize = () => chart?.resize();

onMounted(() => {
    render();
    window.addEventListener('resize', resize);
});
onBeforeUnmount(() => {
    window.removeEventListener('resize', resize);
    chart?.dispose();
    chart = null;
});
watch(() => [props.months, theme.darkMode], render, { deep: true });
watch(view, async (v) => {
    if (v !== 'chart') {
        chart?.dispose();
        chart = null;
        return;
    }
    await nextTick();
    render();
});
</script>

<template>
    <div>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <!-- Legend: identity is never colour alone -->
            <ul class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-text-muted dark:text-text-dark-muted">
                <li v-for="s in SERIES" :key="s.key" class="flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 rounded-sm" :style="{ background: colour(s) }"></span>
                    {{ s.label }} <span class="font-semibold text-text-base dark:text-text-inverted">{{ money(totals[s.key]) }}</span>
                </li>
            </ul>
            <div class="inline-flex rounded-lg bg-gray-100 p-0.5 text-xs dark:bg-white/5" role="tablist">
                <button
                    v-for="v in ['chart', 'table']"
                    :key="v"
                    type="button"
                    role="tab"
                    :aria-selected="view === v"
                    class="rounded-md px-3 py-1 font-medium capitalize transition"
                    :class="view === v ? 'bg-white text-mustBlue shadow-sm dark:bg-background-dark-card dark:text-text-inverted' : 'text-text-muted'"
                    @click="view = v"
                >
                    {{ v }}
                </button>
            </div>
        </div>

        <div v-show="view === 'chart'" ref="el" class="h-72 w-full" role="img" aria-label="Support money given per month, stacked by school fees, basic needs, family support and businesses"></div>

        <!-- Each heading and amount stays on one line; on narrow screens the table scrolls sideways
             inside this box, with the Month column kept in view. -->
        <div v-if="view === 'table'" class="max-h-72 overflow-auto">
            <table class="w-full min-w-max text-right text-sm">
                <thead class="sticky top-0 z-10 bg-white text-xs text-text-muted dark:bg-[#16191d]">
                    <tr class="border-b border-mustBorder-light dark:border-mustBorder-dark">
                        <th class="sticky left-0 bg-white py-2 pl-1 pr-4 text-left font-medium dark:bg-[#16191d]">Month</th>
                        <th v-for="s in SERIES" :key="s.key" class="whitespace-nowrap px-4 py-2 font-medium">{{ s.label }}</th>
                        <th class="whitespace-nowrap py-2 pl-4 pr-1 font-medium">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mustBorder-light dark:divide-mustBorder-dark">
                    <tr v-for="m in [...months].reverse()" :key="m.month">
                        <td class="sticky left-0 whitespace-nowrap bg-white py-2 pl-1 pr-4 text-left dark:bg-[#16191d]">{{ m.label }}</td>
                        <td v-for="s in SERIES" :key="s.key" class="whitespace-nowrap px-4 py-2 tabular-nums text-text-muted">{{ m[s.key] ? money(m[s.key]) : '—' }}</td>
                        <td class="whitespace-nowrap py-2 pl-4 pr-1 font-semibold tabular-nums">{{ money(monthTotal(m)) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

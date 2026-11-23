<!-- eslint-disable prettier/prettier -->
<!-- resources/js/Components/DataTable.vue -->
 <!-- eslint-disable prettier/prettier -->
<template>
    <div
        class="surface overflow-hidden rounded-2xl transition-colors duration-200">
        <!-- Search and Filters -->
        <div
            class="border-b border-mustBorder-light bg-transparent p-3 transition-colors duration-200 dark:border-mustBorder-dark dark:bg-transparent sm:p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex-1 sm:max-w-xs">
                    <!-- Search Input -->
                    <TextInput v-model="search" class="w-full" placeholder="Search..." aria-label="Search" />
                </div>

                <div class="flex items-center gap-2 self-end sm:self-auto">
                    <button v-if="hasActiveFilters" type="button" @click="resetFilters"
                        class="whitespace-nowrap text-sm font-medium text-text-muted hover:text-mustGreen hover:underline">
                        Reset filters
                    </button>
                    <!-- Opens the filter row below -->
                    <button v-if="hasFilters" type="button" @click="filtersOpen = !filtersOpen"
                        class="btn-outline inline-flex items-center gap-2 whitespace-nowrap"
                        :class="{ '!border-mustGreen !text-mustGreen': filtersOpen || activeFilterCount }"
                        :aria-expanded="filtersOpen ? 'true' : 'false'" aria-controls="datatable-filters">
                        <SlidersHorizontal class="h-4 w-4" aria-hidden="true" />
                        Filter
                        <span v-if="activeFilterCount"
                            class="rounded-full bg-mustGreen px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">
                            {{ activeFilterCount }}
                        </span>
                        <ChevronDown class="h-4 w-4 transition-transform" :class="{ 'rotate-180': filtersOpen }" aria-hidden="true" />
                    </button>
                </div>
            </div>

            <!-- Filter row -->
            <div v-if="hasFilters" v-show="filtersOpen" id="datatable-filters"
                class="mt-3 grid gap-3 border-t border-mustBorder-light pt-3 dark:border-mustBorder-dark sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="(filterConfig, filterKey) in filtersConfig" :key="filterKey" class="min-w-0">
                    <label :for="`dt-filter-${filterKey}`"
                        class="mb-1 block text-xs font-medium uppercase tracking-wider text-text-muted">
                        {{ filterConfig.label || filterKey }}
                    </label>
                    <select v-if="filterConfig.component === 'select'" :id="`dt-filter-${filterKey}`"
                        v-model="filterValues[filterKey]" class="text-input w-full">
                        <option :value="''">
                            {{ filterConfig.placeholder || `All ${(filterConfig.label || filterKey).toLowerCase()}` }}
                        </option>
                        <option v-for="option in filterConfig.options" :key="option.value || option"
                            :value="option.value || option">
                            {{ option.label || option }}
                        </option>
                    </select>
                    <TextInput v-else :id="`dt-filter-${filterKey}`" v-model="filterValues[filterKey]"
                        :placeholder="filterConfig.placeholder || `Filter by ${filterKey}`" class="text-input w-full" />
                </div>
            </div>
        </div>

        <!-- Table - Desktop View -->
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-mustBorder-light dark:divide-mustBorder-dark">
                <thead class="bg-transparent transition-colors duration-200 dark:bg-transparent">
                    <tr>
                        <th v-for="column in columns" :key="column.key" scope="col"
                            class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-text-base transition-colors duration-200 dark:text-text-inverted lg:px-6"
                            @click="sort(column.key)" :class="{
                                'bg-transparent':
                                    sortBy === column.key,
                            }">
                            <div class="flex items-center">
                                {{ column.label }}
                                <span v-if="sortBy === column.key" class="ml-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" :class="{
                                        'rotate-180 transform':
                                            sortDirection === 'desc',
                                    }" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </div>
                        </th>
                        <th v-if="$slots.actions"
                            class="bg-transparent px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-text-base transition-colors duration-200 dark:bg-transparent dark:text-text-inverted lg:px-6">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="divide-y divide-mustBorder-light bg-transparent transition-colors duration-200 dark:divide-mustBorder-dark dark:bg-transparent">
                    <template v-if="pageData.length > 0">
                        <tr v-for="(item, index) in pageData" :key="item.id"
                            class="transition-colors duration-200 hover:bg-gray-50 dark:hover:bg-white/5"
                            :class="{
                                'bg-gray-50/60 dark:bg-white/[0.02]':
                                    index % 2 === 0,
                            }">
                            <td v-for="column in columns" :key="column.key"
                                class="whitespace-nowrap px-4 py-2 text-text-base transition-colors duration-200 dark:text-text-dark-muted lg:px-6">
                                <slot :name="`column-${column.key}`" :item="item">
                                    {{ getNestedValue(item, column.key) }}
                                </slot>
                            </td>
                            <td v-if="$slots.actions"
                                class="flex justify-end whitespace-nowrap px-4 py-2 text-right text-sm font-medium text-text-base transition-colors duration-200 dark:text-text-inverted lg:px-6">
                                <slot name="actions" :item="item"></slot>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty State -->
                    <template v-else>
                        <tr>
                            <td :colspan="columns.length + 1" class="px-4 py-4 text-center lg:px-6">
                                <NoRecordFound message="No records found"
                                    description="Try adjusting your search or filter criteria or">
                                    <button @click="resetFilters"
                                        class="mt-2 text-sm font-medium text-mustGreen hover:underline">
                                        Clear filters
                                    </button>
                                </NoRecordFound>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="md:hidden">
            <template v-if="pageData.length > 0">
                <div class="divide-y divide-mustBorder-light dark:divide-mustBorder-dark">
                    <div v-for="(item, index) in pageData" :key="item.id"
                        class="p-4 transition-colors duration-200 hover:bg-gray-50 dark:hover:bg-white/5"
                        :class="{
                            'bg-gray-50/60 dark:bg-white/[0.02]':
                                index % 2 === 0,
                        }">
                        <!-- Mobile Card Content -->
                        <div class="space-y-3">
                            <div v-for="column in columns" :key="column.key"
                                class="flex flex-col sm:flex-row sm:justify-between">
                                <div
                                    class="mb-1 text-xs font-medium uppercase tracking-wider text-text-base dark:text-text-inverted sm:mb-0 sm:w-1/3">
                                    {{ column.label }}
                                </div>
                                <div class="text-sm text-text-base dark:text-text-dark-muted sm:w-2/3 sm:text-right">
                                    <slot :name="`column-${column.key}`" :item="item">
                                        {{ getNestedValue(item, column.key) }}
                                    </slot>
                                </div>
                            </div>

                            <!-- Actions for mobile -->
                            <div v-if="$slots.actions"
                                class="flex justify-end border-t border-mustBorder-light pt-2 dark:border-mustBorder-dark">
                                <slot name="actions" :item="item"></slot>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Mobile Empty State -->
            <template v-else>
                <div class="p-6 text-center">
                    <NoRecordFound message="No records found"
                        description="Try adjusting your search or filter criteria or">
                        <button @click="resetFilters" class="mt-2 text-sm font-medium text-mustGreen hover:underline">
                            Clear filters
                        </button>
                    </NoRecordFound>
                </div>
            </template>
        </div>

        <!-- Pagination -->
        <div
            class="flex flex-col gap-3 border-t border-mustBorder-light bg-transparent px-3 py-3 transition-colors duration-200 dark:border-mustBorder-dark dark:bg-transparent sm:flex-row sm:items-center sm:justify-between sm:px-4">
            <div class="flex flex-col items-center gap-3 sm:flex-row">
            <!-- Items Per Page -->
            <label class="sr-only" for="datatable-per-page">Rows per page</label>
            <select id="datatable-per-page" v-model="perPage" class="text-input !py-1.5 w-auto">
                <option v-for="option in perPageOptions" :value="option" :key="option">
                    {{ option }} per page
                </option>
            </select>
            <div
                class="text-center text-xs text-text-base transition-colors duration-200 dark:text-text-dark-muted sm:text-left sm:text-sm">
                Showing
                <span class="font-medium">{{
                    meta.from?.toLocaleString()
                    }}</span>
                to
                <span class="font-medium">{{ meta.to?.toLocaleString() }}</span>
                of
                <span class="font-medium">{{
                    meta.total?.toLocaleString()
                    }}</span>
                results
            </div>
            </div>
            <div class="flex justify-center sm:justify-end">
                <Pagination @goToPage="goToPage" :links="props.serverSide ? meta.links : clientPaginationLinks
                    " />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import TextInput from '@/Components/FormElements/TextInput.vue';
import NoRecordFound from '@/Components/Common/NoRecordFound.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import { ChevronDown, SlidersHorizontal } from 'lucide-vue-next';

const props = defineProps({
    data: Object,
    columns: Array,
    routeName: {
        required: false,
        type: String,
    },
    routeParams: { type: Object, default: () => ({}) },
    filters: Object,
    serverSide: {
        type: Boolean,
        default: true,
    },
    perPageOptions: {
        type: Array,
        default: () => [10, 25, 50, 100],
    },
    filtersConfig: {
        type: Object,
        default: () => ({}),
    },
    sortBy: {
        type: String,
        default: 'created_at',
    },
    sortDirection: {
        type: String,
        default: 'desc',
    },
});

// Convert paginator to expected meta format
const meta = computed(() => {
    if (props.serverSide) {
        return {
            from: props.data.from,
            to: props.data.to,
            total: props.data.total,
            per_page: props.data.perPage,
            current_page: props.data.currentPage,
            last_page: props.data.lastPage,
            links: formatLinks(props.data.links),
        };
    }
    return {
        from: from.value,
        to: to.value,
        total: props.data.length,
        per_page: perPage.value,
        current_page: currentPage.value,
        last_page: lastPage.value,
        links: [],
    };
});

// Format Laravel pagination links
const formatLinks = (links) => {
    return links.map((link) => ({
        url: link.url,
        label: link.label,
        active: link.active,
    }));
};

// Start from the filters already in the URL (so they survive a page reload).
const filterValues = ref(
    Object.fromEntries(
        Object.keys(props.filtersConfig || {}).map((key) => [key, props.filters?.[key] ?? '']),
    ),
);

const hasFilters = computed(() => Object.keys(props.filtersConfig || {}).length > 0);
const activeFilterCount = computed(
    () => Object.values(filterValues.value).filter((val) => val !== '' && val !== null).length,
);
// The filter row starts open when a filter is already applied.
const filtersOpen = ref(activeFilterCount.value > 0);

// Compute if we have active filters
const hasActiveFilters = computed(() => {
    const url = new URL(window.location.href);
    const searchParams = Object.fromEntries([...url.searchParams.entries()]);

    return (
        search.value ||
        Object.values(filterValues.value).some(
            (val) => val !== '' && val !== null,
        ) ||
        sortBy.value !== props.columns[0]?.key ||
        sortDirection.value !== 'asc' ||
        Object.keys(searchParams).some((key) => key !== 'page')
    );
});

const pageData = computed(() => {
    if (props.serverSide) {
        return props.data.data;
    }
    return localPagination.value;
});

// Reset all filters
const resetFilters = () => {
    search.value = '';
    sortBy.value = props.columns[0]?.key;
    sortDirection.value = 'asc';
    perPage.value = props.perPageOptions[0];

    // Reset all configured filters
    Object.keys(props.filtersConfig).forEach((key) => {
        filterValues.value[key] = '';
    });

    // Clear all URL parameters
    const url = new URL(window.location.origin + window.location.pathname);
    window.history.replaceState({}, document.title, url.toString());

    // Immediately update the data
    updateData();
};

//local pagination
const currentPage = ref(1);
const localPagination = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return props.data.slice(start, end);
});
const lastPage = computed(() => {
    return Math.ceil(props.data.length / perPage.value);
});
const from = computed(() => {
    return (currentPage.value - 1) * perPage.value + 1;
});
const to = computed(() => {
    return Math.min(currentPage.value * perPage.value, props.data.length);
});

const search = ref(props.data.search || '');
const perPage = ref(props.data.perPage || props.perPageOptions[0]);
const sortBy = ref(props.sortBy || props.data.sort_by || props.columns[0]?.key);
const sortDirection = ref(
    props.sortDirection || props.data.sort_direction || 'desc',
);

let debounceTimer;
watch(
    [search, perPage, sortBy, sortDirection, filterValues],
    () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            updateData();
        }, 300);
    },
    { deep: true },
);

const updateData = () => {
    if (props.serverSide) {
        const params = {
            search: search.value,
            per_page: perPage.value,
            sort_by: sortBy.value,
            sort_direction: sortDirection.value,
            ...Object.fromEntries(
                Object.entries(filterValues.value).filter(
                    // eslint-disable-next-line no-unused-vars
                    ([_, value]) => value !== '' && value !== null,
                ),
            ),
        };

        router.get(route(props.routeName, props.routeParams), params, {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        });
    } else {
        // Sort data if needed
        if (sortBy.value) {
            // eslint-disable-next-line vue/no-mutating-props
            props.data.sort((a, b) => {
                const aValue = getNestedValue(a, sortBy.value);
                const bValue = getNestedValue(b, sortBy.value);

                if (sortDirection.value === 'asc') {
                    return aValue < bValue ? -1 : aValue > bValue ? 1 : 0;
                } else {
                    return aValue > bValue ? -1 : aValue < bValue ? 1 : 0;
                }
            });
        }

        // Filter data if needed
        if (search.value) {
            localPagination.value = localPagination.value.filter((item) => {
                return Object.values(item).some((value) =>
                    String(value)
                        .toLowerCase()
                        .includes(search.value.toLowerCase()),
                );
            });
        }
    }
};

// Helper to get nested object values
const getNestedValue = (obj, path) => {
    return path.split('.').reduce((o, p) => o?.[p], obj);
};

// Sorting function
const sort = (column) => {
    if (sortBy.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortDirection.value = 'asc';
    }
};

// Client-side pagination links
const clientPaginationLinks = computed(() => {
    const links = [];
    const totalPages = lastPage.value;

    // First page
    links.push({
        label: '«',
        url: currentPage.value > 1 ? '1' : null,
        active: false,
    });

    // Previous page
    links.push({
        label: '‹',
        url: currentPage.value > 1 ? (currentPage.value - 1).toString() : null,
        active: false,
    });

    // Page numbers
    const rangeStart = Math.max(1, currentPage.value - 2);
    const rangeEnd = Math.min(totalPages, currentPage.value + 2);

    if (rangeStart > 1) {
        links.push({
            label: '...',
            url: null,
            active: false,
        });
    }

    for (let i = rangeStart; i <= rangeEnd; i++) {
        links.push({
            label: i.toString(),
            url: i.toString(),
            active: i === currentPage.value,
        });
    }

    if (rangeEnd < totalPages) {
        links.push({
            label: '...',
            url: null,
            active: false,
        });
    }

    // Next page
    links.push({
        label: '›',
        url:
            currentPage.value < totalPages
                ? (currentPage.value + 1).toString()
                : null,
        active: false,
    });

    // Last page
    links.push({
        label: '»',
        url: currentPage.value < totalPages ? totalPages.toString() : null,
        active: false,
    });

    return links;
});

// Pagination navigation
const goToPage = (url) => {
    if (props.serverSide) {
        if (!url) return;

        // Get current query parameters
        const currentParams = new URL(window.location.href).searchParams;
        const newParams = new URL(url).searchParams;

        // Create merged params object
        const params = {
            page: newParams.get('page'), // Get the new page number
        };

        // Copy all existing query parameters except page
        currentParams.forEach((value, key) => {
            if (key !== 'page') {
                params[key] = value;
            }
        });

        router.get(route(props.routeName, props.routeParams), params, {
            preserveState: true,
            preserveScroll: true,
        });
    } else {
        if (!url) return;
        currentPage.value = parseInt(url);
    }
};

watch(currentPage, () => {
    if (!props.serverSide) {
        updateData();
    }
});
</script>

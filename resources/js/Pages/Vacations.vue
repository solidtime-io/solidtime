<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { CalendarDaysIcon } from '@heroicons/vue/20/solid';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { TabBar, TabBarItem } from '@/packages/ui/src';
import { ref, computed } from 'vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import VacationRequestList from '@/Components/Common/VacationRequest/VacationRequestList.vue';
import VacationRequestYearCalendar from '@/Components/Common/VacationRequest/VacationRequestYearCalendar.vue';
import VacationRequestCreateModal from '@/Components/Common/VacationRequest/VacationRequestCreateModal.vue';
import { useVacationRequestsQuery } from '@/utils/useVacationRequests';
import { canCreateVacationRequests, canViewOwnVacationRequests } from '@/utils/permissions';

const activeTab = ref<'list' | 'calendar'>('list');
const showCreate = ref(false);

const selectedYear = ref(new Date().getFullYear());

const { data: requests, isLoading } = useVacationRequestsQuery();

const canCreate = computed(() => canCreateVacationRequests());
</script>

<template>
    <AppLayout title="Vacations" data-testid="vacations_view">
        <MainContainer
            class="py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-4 sm:space-x-6">
                <PageTitle :icon="CalendarDaysIcon" title="Vacations" />
                <TabBar v-model="activeTab">
                    <TabBarItem value="list">List</TabBarItem>
                    <TabBarItem value="calendar">Calendar</TabBarItem>
                </TabBar>
            </div>
            <div class="flex items-center gap-3">
                <div v-if="activeTab === 'calendar'" class="flex items-center gap-1">
                    <button
                        class="p-1 rounded hover:bg-tertiary text-text-secondary transition"
                        @click="selectedYear--">
                        ‹
                    </button>
                    <span class="text-sm font-medium text-text-primary w-12 text-center">{{
                        selectedYear
                    }}</span>
                    <button
                        class="p-1 rounded hover:bg-tertiary text-text-secondary transition"
                        @click="selectedYear++">
                        ›
                    </button>
                </div>

                <SecondaryButton v-if="canCreate" :icon="PlusIcon" @click="showCreate = true">
                    Record absence
                </SecondaryButton>
            </div>
        </MainContainer>

        <MainContainer v-if="activeTab === 'list'" class="py-6">
            <VacationRequestList :requests="requests ?? []" :loading="isLoading" />
        </MainContainer>

        <MainContainer v-else class="py-6">
            <VacationRequestYearCalendar :requests="requests ?? []" :year="selectedYear" />
        </MainContainer>

        <VacationRequestCreateModal v-model:show="showCreate" @created="showCreate = false" />
    </AppLayout>
</template>

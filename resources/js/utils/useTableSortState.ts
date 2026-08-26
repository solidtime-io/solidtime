import { useStorage } from '@vueuse/core';
import type { SortDirection } from '@/utils/useSortableTable';

export interface TableSortState<TColumn extends string> {
    sortColumn: TColumn;
    sortDirection: SortDirection;
}

/**
 * The sort a table page remembers between visits, persisted in localStorage.
 *
 * Pages that persist more than the sort (filters, for example) pass the wider defaults
 * and get them back on `tableState`; `mergeDefaults` is forwarded to `useStorage` so
 * nested state can be merged key by key rather than wholesale.
 */
export function useTableSortState<
    TColumn extends string,
    TState extends TableSortState<TColumn> = TableSortState<TColumn>,
>(
    key: string,
    defaults: TState,
    mergeDefaults: boolean | ((storageValue: TState, defaults: TState) => TState) = true
) {
    const tableState = useStorage<TState>(key, defaults, undefined, { mergeDefaults });

    function handleSort(column: TColumn, direction: SortDirection) {
        tableState.value.sortColumn = column;
        tableState.value.sortDirection = direction;
    }

    return { tableState, handleSort };
}

import {
    getCoreRowModel,
    getSortedRowModel,
    sortingFns,
    type ColumnDef,
    type Row,
    type SortingFn,
    type SortingState,
    useVueTable,
} from '@tanstack/vue-table';
import { computed, type ComputedRef } from 'vue';

export type SortDirection = 'asc' | 'desc';

/**
 * Comparator for every sortable column: empty values (`null`/`undefined`) sort last in
 * both directions and compare as equal, so the tie-break orders them. TanStack's own
 * `sortUndefined` cannot do this: `'last'` bypasses the tie-break and the default flips
 * empty rows to the top when descending.
 */
function sortEmptyLast<TData>(getSorting: () => SortingState): SortingFn<TData> {
    return (rowA: Row<TData>, rowB: Row<TData>, columnId: string) => {
        const a = rowA.getValue(columnId);
        const b = rowB.getValue(columnId);

        if (a == null && b == null) {
            return 0;
        }
        if (a == null || b == null) {
            const emptyLast = a == null ? 1 : -1;
            const desc = getSorting().find((entry) => entry.id === columnId)?.desc ?? false;
            return desc ? -emptyLast : emptyLast;
        }
        return typeof a === 'string' || typeof b === 'string'
            ? sortingFns.alphanumeric(rowA, rowB, columnId)
            : sortingFns.basic(rowA, rowB, columnId);
    };
}

/**
 * Requires `id` to be one of the table's sortable columns, so a mistyped id is a compile
 * error instead of a column that silently stops sorting. `sortingFn` is forbidden
 * because the composable always installs its own comparator.
 */
export type SortableColumnDef<TData, TColumn extends string> = ColumnDef<TData, unknown> & {
    id: TColumn;
    sortingFn?: never;
};

export function useSortableTable<TData, TColumn extends string>(options: {
    data: () => TData[];
    columns: () => SortableColumnDef<TData, TColumn>[];
    sortColumn: () => TColumn;
    sortDirection: () => SortDirection;
    tieBreakColumn?: TColumn;
}): {
    sortedRows: ComputedRef<TData[]>;
    descFirstColumns: ComputedRef<ReadonlySet<TColumn>>;
    nextDirection: (column: TColumn) => SortDirection;
} {
    const sorting = computed<SortingState>(() => [
        {
            id: options.sortColumn(),
            desc: options.sortDirection() === 'desc',
        },
        ...(options.tieBreakColumn && options.sortColumn() !== options.tieBreakColumn
            ? [{ id: options.tieBreakColumn, desc: false }]
            : []),
    ]);

    const resolvedColumns = computed<ColumnDef<TData, unknown>[]>(() =>
        options.columns().map((column) => ({
            ...column,
            sortUndefined: false as const,
            sortingFn: sortEmptyLast<TData>(() => sorting.value),
        }))
    );

    const descFirstColumns = computed<ReadonlySet<TColumn>>(
        () =>
            new Set(
                options
                    .columns()
                    .filter((column) => column.sortDescFirst)
                    .map((column) => column.id)
            )
    );

    function nextDirection(column: TColumn): SortDirection {
        if (options.sortColumn() === column) {
            return options.sortDirection() === 'asc' ? 'desc' : 'asc';
        }
        return descFirstColumns.value.has(column) ? 'desc' : 'asc';
    }

    const table = useVueTable({
        get data() {
            return options.data();
        },
        get columns() {
            return resolvedColumns.value;
        },
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
        state: {
            get sorting() {
                return sorting.value;
            },
        },
        manualSorting: false,
    });

    const sortedRows = computed(() => table.getRowModel().rows.map((row) => row.original));

    return { sortedRows, descFirstColumns, nextDirection };
}

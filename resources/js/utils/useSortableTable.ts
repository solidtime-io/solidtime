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
 * The comparator every sortable column gets: a row whose accessor returns `undefined`
 * sorts to the bottom in both directions, and two such rows compare as equal so the
 * tie-break decides their order.
 *
 * TanStack cannot express that combination. Its `sortUndefined: 'last'` keeps empty rows
 * at the bottom but returns a non-zero result even when both rows are empty, which
 * swallows the tie-break and leaves those rows in API (created_at) order; its default of
 * `1` returns zero for that case but flips empty rows to the top when descending.
 */
function sortEmptyLast<TData>(getDirection: () => SortDirection): SortingFn<TData> {
    return (rowA: Row<TData>, rowB: Row<TData>, columnId: string) => {
        const a = rowA.getValue(columnId);
        const b = rowB.getValue(columnId);

        if (a === undefined && b === undefined) {
            return 0;
        }
        if (a === undefined || b === undefined) {
            const emptyLast = a === undefined ? 1 : -1;
            return getDirection() === 'desc' ? -emptyLast : emptyLast;
        }
        return typeof a === 'string' || typeof b === 'string'
            ? sortingFns.alphanumeric(rowA, rowB, columnId)
            : sortingFns.basic(rowA, rowB, columnId);
    };
}

/**
 * A column definition whose `id` has to be one of the table's sortable columns, so a
 * renamed or mistyped id is a compile error rather than a column that silently stops
 * sorting: TanStack drops sort entries for ids it cannot resolve, leaving the rows in
 * their original order with no chevron and no error.
 */
export type SortableColumnDef<TData, TColumn extends string> = ColumnDef<TData, unknown> & {
    id: TColumn;
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
        options.columns().map((column) =>
            column.sortingFn
                ? column
                : {
                      ...column,
                      sortUndefined: false as const,
                      sortingFn: sortEmptyLast<TData>(options.sortDirection),
                  }
        )
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

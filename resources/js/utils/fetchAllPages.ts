/**
 * Fetches all pages from a paginated Laravel API endpoint.
 * Uses `meta.last_page` to determine the total number of pages,
 * so only a single request is made when all data fits on one page.
 */
export async function fetchAllPages<T>(
    fetchPage: (page: number) => Promise<{
        data: T[];
        meta: { per_page: number; last_page: number };
    }>
): Promise<T[]> {
    const firstResponse = await fetchPage(1);
    const allItems: T[] = [...firstResponse.data];
    const { last_page } = firstResponse.meta;

    for (let page = 2; page <= last_page; page++) {
        const response = await fetchPage(page);
        allItems.push(...response.data);
    }

    return allItems;
}

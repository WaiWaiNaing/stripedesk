import { computed, ref } from 'vue';

/**
 * Client-side search over admin list rows. Table handles pagination, column filters, and sorting.
 *
 * @param {import('vue').Ref<Array>} itemsRef
 * @param {(row: object, q: string) => boolean} matchFn
 */
export function useAdminTableSearch(itemsRef, matchFn) {
  const searchText = ref('');
  const filteredItems = computed(() => {
    const list = itemsRef.value || [];
    const q = searchText.value.trim().toLowerCase();
    if (!q) return list;
    return list.filter((row) => matchFn(row, q));
  });
  return { searchText, filteredItems };
}

/** Shared pagination UI for admin tables (Ant Design Vue). */
export const adminTablePagination = {
  showSizeChanger: true,
  pageSizeOptions: ['10', '20', '50', '100'],
  showTotal: (total) => `Total ${total} items`,
  defaultPageSize: 10,
};

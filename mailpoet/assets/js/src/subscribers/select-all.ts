export const SELECT_ALL_WARNING_THRESHOLD = 10000;

export type SelectAllBannerMode = 'hidden' | 'offer' | 'active';

export function selectAllBannerState(params: {
  pageItemCount: number;
  totalCount: number;
  totalPages: number;
  isPageFullySelected: boolean;
  isSelectAll: boolean;
}): SelectAllBannerMode {
  const {
    pageItemCount,
    totalCount,
    totalPages,
    isPageFullySelected,
    isSelectAll,
  } = params;
  if (totalCount <= 0 || totalPages <= 1) {
    return 'hidden';
  }
  if (isSelectAll) {
    return 'active';
  }
  if (isPageFullySelected && pageItemCount > 0) {
    return 'offer';
  }
  return 'hidden';
}

export function shouldWarnLargeOperation(
  count: number,
  threshold: number = SELECT_ALL_WARNING_THRESHOLD,
): boolean {
  return count > threshold;
}

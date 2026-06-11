export type SubscriberBulkActionScope = {
  group: string;
  filter: Record<string, string>;
  search: string;
  selection: number[];
  selectAll?: boolean;
};

export function buildBulkActionPayload(
  action: string,
  scope: SubscriberBulkActionScope,
  extra: Record<string, unknown> = {},
): Record<string, unknown> {
  const selectAll = Boolean(scope.selectAll);
  return {
    action,
    selection: selectAll ? [] : scope.selection,
    select_all: selectAll,
    group: scope.group,
    search: scope.search,
    filter: scope.filter,
    ...extra,
  };
}

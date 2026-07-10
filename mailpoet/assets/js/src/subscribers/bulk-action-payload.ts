export type SubscriberBulkActionScope = {
  group: string;
  filter: Record<string, string>;
  search: string;
  selection: number[];
  selectAll?: boolean;
};

const AUTOMATION_TRIGGERING_ACTIONS = new Set([
  'moveToList',
  'addToList',
  'addTag',
  'removeTag',
]);

export function canTriggerAutomations(action: string): boolean {
  return AUTOMATION_TRIGGERING_ACTIONS.has(action);
}

export function buildBulkActionPayload(
  action: string,
  scope: SubscriberBulkActionScope,
  extra: Record<string, unknown> = {},
): Record<string, unknown> {
  const selectAll = Boolean(scope.selectAll);
  const { trigger_automations: triggerAutomations, ...actionExtra } = extra;
  return {
    action,
    selection: selectAll ? [] : scope.selection,
    select_all: selectAll,
    group: scope.group,
    search: scope.search,
    filter: scope.filter,
    ...actionExtra,
    ...(canTriggerAutomations(action) && typeof triggerAutomations === 'boolean'
      ? { trigger_automations: triggerAutomations }
      : {}),
  };
}

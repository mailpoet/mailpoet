import { __unstableAwaitPromise as AwaitPromise } from '@wordpress/data-controls';
import { legacyApiFetch, ListingItem } from './legacy-api';
import { AutomationItem } from './types';
import { AutomationStatus } from '../automation';

const mapToAutomation = (item: ListingItem): AutomationItem => ({
  id: item.id,
  name: item.subject,
  status: item.deleted_at ? AutomationStatus.TRASH : item.status,
  stats: { totals: { entered: 0, in_progress: 0, exited: 0 } },
  isLegacy: true,
});

export function* loadLegacyAutomations() {
  const response: unknown[] = yield AwaitPromise(
    Promise.all([
      legacyApiFetch({
        endpoint: 'newsletters',
        method: 'listing',
        'data[params][type]': 'welcome',
      }),
      legacyApiFetch({
        endpoint: 'newsletters',
        method: 'listing',
        'data[params][type]': 'automatic',
      }),
    ]),
  );

  const automations = response
    .flatMap<ListingItem>(({ data }: { data: ListingItem[] }) => data)
    .map<AutomationItem>(mapToAutomation);

  return {
    type: 'SET_LEGACY_AUTOMATIONS',
    automations,
  } as const;
}

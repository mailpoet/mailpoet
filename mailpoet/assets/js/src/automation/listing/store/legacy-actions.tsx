import { __ } from '@wordpress/i18n';
import { __unstableAwaitPromise as AwaitPromise } from '@wordpress/data-controls';
import { dispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { legacyApiFetch, ListingItem } from './legacy-api';
import { AutomationItem } from './types';
import { Automation, AutomationStatus } from '../automation';

const createSuccessNotice = (content: string, options?: unknown) =>
  dispatch(noticesStore).createSuccessNotice(content, options);

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

export function* trashLegacyAutomation(automation: Automation) {
  const data: { data: ListingItem } = yield AwaitPromise(
    legacyApiFetch({
      endpoint: 'newsletters',
      method: 'trash',
      'data[id]': `${automation.id}`,
    }),
  );

  createSuccessNotice(__('1 automation moved to the Trash.', 'mailpoet'));

  return {
    type: 'UPDATE_LEGACY_AUTOMATION',
    automation: mapToAutomation(data.data),
  } as const;
}

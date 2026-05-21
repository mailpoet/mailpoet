import { __, sprintf } from '@wordpress/i18n';
import { __unstableAwaitPromise as AwaitPromise } from '@wordpress/data-controls';
import { dispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import {
  getLegacyNewsletters,
  legacyApiFetch,
  ListingItem,
} from './legacy-api';
import { getDescription } from './legacy-description';
import { AutomationItem } from './types';
import { Automation, AutomationStatus } from '../automation';

type NoticeOptions = {
  showSuccessNotice?: boolean;
};

const createSuccessNotice = (content: string, options?: unknown) =>
  dispatch(noticesStore).createSuccessNotice(content, options);

const removeNotice = (id: string) => dispatch(noticesStore).removeNotice(id);

const shouldShowSuccessNotice = (options?: NoticeOptions): boolean =>
  options?.showSuccessNotice !== false;

const mapToAutomation = (item: ListingItem): AutomationItem => ({
  id: item.id,
  name: item.subject,
  status: item.deleted_at ? AutomationStatus.TRASH : item.status,
  stats: {
    totals: {
      entered: item.total_scheduled + item.total_sent,
      in_progress: item.total_scheduled,
      exited: item.total_sent,
    },
  },
  isLegacy: true,
  description: getDescription(item),
});

export function* loadLegacyAutomations() {
  const response: ListingItem[][] = yield AwaitPromise(
    Promise.all([
      getLegacyNewsletters('welcome'),
      getLegacyNewsletters('automatic'),
    ]),
  );

  const automations = response
    .flatMap((items) => items)
    .map<AutomationItem>(mapToAutomation);

  return {
    type: 'SET_LEGACY_AUTOMATIONS',
    automations,
  } as const;
}

export function* trashLegacyAutomation(
  automation: Automation,
  options?: NoticeOptions,
) {
  yield AwaitPromise(
    legacyApiFetch({
      endpoint: 'newsletters',
      method: 'trash',
      'data[id]': `${automation.id}`,
    }),
  );

  if (shouldShowSuccessNotice(options)) {
    void createSuccessNotice(
      sprintf(
        __('Automation "%s" was moved to the trash.', 'mailpoet'),
        automation.name,
      ),
    );
  }

  return {
    type: 'UPDATE_LEGACY_AUTOMATION_STATUS',
    id: automation.id,
    status: AutomationStatus.TRASH,
  } as const;
}

export function* restoreLegacyAutomation(
  automation: Automation,
  options?: NoticeOptions,
) {
  const data: { data: ListingItem } = yield AwaitPromise(
    legacyApiFetch({
      endpoint: 'newsletters',
      method: 'restore',
      'data[id]': `${automation.id}`,
    }),
  );

  void removeNotice(`automation-trashed-${automation.id}`);

  if (shouldShowSuccessNotice(options)) {
    void createSuccessNotice(
      sprintf(
        __('Automation "%s" was restored from the trash.', 'mailpoet'),
        automation.name,
      ),
    );
  }

  return {
    type: 'UPDATE_LEGACY_AUTOMATION_STATUS',
    id: automation.id,
    status: data.data.status,
  } as const;
}

export function* deleteLegacyAutomation(
  automation: Automation,
  options?: NoticeOptions,
) {
  yield AwaitPromise(
    legacyApiFetch({
      endpoint: 'newsletters',
      method: 'delete',
      'data[id]': `${automation.id}`,
    }),
  );

  if (shouldShowSuccessNotice(options)) {
    void createSuccessNotice(
      sprintf(
        __(
          'Automation "%s" and all associated data were permanently deleted.',
          'mailpoet',
        ),
        automation.name,
      ),
    );
  }

  return {
    type: 'DELETE_LEGACY_AUTOMATION',
    id: automation.id,
  } as const;
}

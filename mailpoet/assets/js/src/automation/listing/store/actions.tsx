import { dispatch } from '@wordpress/data';
import { apiFetch } from '@wordpress/data-controls';
import { Button } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { Automation, AutomationStatus } from '../automation';
import { UndoTrashButton } from '../components/actions';
import { getAutomationEditorUrl } from '../urls';

type NoticeOptions = {
  showSuccessNotice?: boolean;
};

const createSuccessNotice = (content: string, options?: unknown) =>
  dispatch(noticesStore).createSuccessNotice(content, options);

const removeNotice = (id: string) => dispatch(noticesStore).removeNotice(id);

const shouldShowSuccessNotice = (options?: NoticeOptions): boolean =>
  options?.showSuccessNotice !== false;

export function* loadAutomations() {
  const data = yield apiFetch({
    path: `/automations`,
  });

  return {
    type: 'SET_AUTOMATIONS',
    automations: Array.isArray(data.data?.items) ? data.data.items : [],
  } as const;
}

export function* duplicateAutomation(
  automation: Automation,
  options?: NoticeOptions,
) {
  const data = yield apiFetch({
    path: `/automations/${automation.id}/duplicate`,
    method: 'POST',
  });

  if (shouldShowSuccessNotice(options)) {
    void createSuccessNotice(
      // translators: %s is the automation name
      sprintf(
        __('Automation "%s" was duplicated.', 'mailpoet'),
        automation.name,
      ),
    );
  }

  return {
    type: 'ADD_AUTOMATION',
    automation: data.data,
  } as const;
}

export function* trashAutomation(
  automation: Automation,
  options?: NoticeOptions,
) {
  const data = yield apiFetch({
    path: `/automations/${automation.id}`,
    method: 'PUT',
    data: {
      status: AutomationStatus.TRASH,
    },
  });

  const message = sprintf(
    __('Automation "%s" was moved to the trash.', 'mailpoet'),
    automation.name,
  );
  if (shouldShowSuccessNotice(options)) {
    void createSuccessNotice(message, {
      id: `automation-trashed-${automation.id}`,
      __unstableHTML: (
        <p>
          {message}{' '}
          <UndoTrashButton
            automation={automation}
            previousStatus={automation.status}
          />
        </p>
      ),
    });
  }

  return {
    type: 'UPDATE_AUTOMATION',
    automation: data.data,
  } as const;
}

export function* restoreAutomation(
  automation: Automation,
  status: AutomationStatus,
  options?: NoticeOptions,
) {
  const data = yield apiFetch({
    path: `/automations/${automation.id}`,
    method: 'PUT',
    data: {
      status,
    },
  });

  void removeNotice(`automation-trashed-${automation.id}`);

  const message = sprintf(
    __('Automation "%s" was restored from the trash.', 'mailpoet'),
    automation.name,
  );
  if (shouldShowSuccessNotice(options)) {
    void createSuccessNotice(message, {
      __unstableHTML: (
        <p>
          {message}{' '}
          <Button variant="link" href={getAutomationEditorUrl(automation)}>
            {__('Edit automation', 'mailpoet')}
          </Button>
        </p>
      ),
    });
  }

  return {
    type: 'UPDATE_AUTOMATION',
    automation: data.data,
  } as const;
}

export function* deleteAutomation(
  automation: Automation,
  options?: NoticeOptions,
) {
  yield apiFetch({
    path: `/automations/${automation.id}`,
    method: 'DELETE',
  });

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
    type: 'DELETE_AUTOMATION',
    automation,
  } as const;
}

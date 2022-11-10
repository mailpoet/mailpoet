import { dispatch, StoreDescriptor } from '@wordpress/data';
import { apiFetch } from '@wordpress/data-controls';
import { __, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { Automation, AutomationStatus } from '../automation';
import { EditAutomation, UndoTrashButton } from '../components/actions';

const createSuccessNotice = (content: string, options?: unknown) =>
  dispatch(noticesStore as StoreDescriptor).createSuccessNotice(
    content,
    options,
  );

const removeNotice = (id: string) =>
  dispatch(noticesStore as StoreDescriptor).removeNotice(id);

export function* loadAutomations() {
  const data = yield apiFetch({
    path: `/automations`,
  });

  return {
    type: 'SET_AUTOMATIONS',
    automations: data.data,
  } as const;
}

export function* duplicateAutomation(automation: Automation) {
  const data = yield apiFetch({
    path: `/automations/${automation.id}/duplicate`,
    method: 'POST',
  });

  void createSuccessNotice(
    // translators: %s is the automation name
    sprintf(__('Automation "%s" was duplicated.', 'mailpoet'), automation.name),
  );

  return {
    type: 'ADD_AUTOMATION',
    automation: data.data,
  } as const;
}

export function* trashAutomation(automation: Automation) {
  const data = yield apiFetch({
    path: `/automations/${automation.id}`,
    method: 'PUT',
    data: {
      status: AutomationStatus.TRASH,
    },
  });

  const message = __('1 automation moved to the Trash.', 'mailpoet');
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

  return {
    type: 'UPDATE_AUTOMATION',
    automation: data.data,
  } as const;
}

export function* restoreAutomation(
  automation: Automation,
  status: AutomationStatus,
) {
  const data = yield apiFetch({
    path: `/automations/${automation.id}`,
    method: 'PUT',
    data: {
      status,
    },
  });

  void removeNotice(`automation-trashed-${automation.id}`);

  const message = __('1 automation restored from the Trash.', 'mailpoet');
  void createSuccessNotice(message, {
    __unstableHTML: (
      <p>
        {message}{' '}
        <EditAutomation
          automation={automation}
          label={__('Edit automation', 'mailpoet')}
        />
      </p>
    ),
  });

  return {
    type: 'UPDATE_AUTOMATION',
    automation: data.data,
  } as const;
}

export function* deleteAutomation(automation: Automation) {
  yield apiFetch({
    path: `/automations/${automation.id}`,
    method: 'DELETE',
  });

  void createSuccessNotice(
    __('1 automation and all associated data permanently deleted.', 'mailpoet'),
  );

  return {
    type: 'DELETE_AUTOMATION',
    automation,
  } as const;
}

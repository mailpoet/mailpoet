import { dispatch, StoreDescriptor } from '@wordpress/data';
import { apiFetch } from '@wordpress/data-controls';
import { __, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { Workflow, WorkflowStatus } from '../workflow';

const createSuccessNotice = (content: string, options?: unknown) =>
  dispatch(noticesStore as StoreDescriptor).createSuccessNotice(
    content,
    options,
  );

export function* loadWorkflows() {
  const data = yield apiFetch({
    path: `/workflows`,
  });

  return {
    type: 'SET_WORKFLOWS',
    workflows: data.data,
  } as const;
}

export function* duplicateWorkflow(workflow: Workflow) {
  const data = yield apiFetch({
    path: `/workflows/${workflow.id}/duplicate`,
    method: 'POST',
  });

  void createSuccessNotice(
    // translators: %s is the workflow name
    sprintf(__('Automation "%s" was duplicated.', 'mailpoet'), workflow.name),
  );

  return {
    type: 'ADD_WORKFLOW',
    workflow: data.data,
  } as const;
}

export function* trashWorkflow(workflow: Workflow) {
  const data = yield apiFetch({
    path: `/workflows/${workflow.id}`,
    method: 'PUT',
    data: {
      status: WorkflowStatus.TRASH,
    },
  });

  void createSuccessNotice(__('1 automation moved to the Trash.', 'mailpoet'));

  return {
    type: 'UPDATE_WORKFLOW',
    workflow: data.data,
  } as const;
}

export function* restoreWorkflow(workflow: Workflow, status: WorkflowStatus) {
  const data = yield apiFetch({
    path: `/workflows/${workflow.id}`,
    method: 'PUT',
    data: {
      status,
    },
  });

  void createSuccessNotice(
    __('1 automation restored from the Trash.', 'mailpoet'),
  );

  return {
    type: 'UPDATE_WORKFLOW',
    workflow: data.data,
  } as const;
}

export function* deleteWorkflow(workflow: Workflow) {
  yield apiFetch({
    path: `/workflows/${workflow.id}`,
    method: 'DELETE',
  });

  void createSuccessNotice(
    __('1 automation and all associated data permanently deleted.', 'mailpoet'),
  );

  return {
    type: 'DELETE_WORKFLOW',
    workflow,
  } as const;
}

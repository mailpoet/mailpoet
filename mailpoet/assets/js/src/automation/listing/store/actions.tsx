import { dispatch, StoreDescriptor } from '@wordpress/data';
import { apiFetch } from '@wordpress/data-controls';
import { __, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { Workflow, WorkflowStatus } from '../workflow';
import { EditWorkflow, UndoTrashButton } from '../components/actions';

const createSuccessNotice = (content: string, options?: unknown) =>
  dispatch(noticesStore as StoreDescriptor).createSuccessNotice(
    content,
    options,
  );

const removeNotice = (id: string) =>
  dispatch(noticesStore as StoreDescriptor).removeNotice(id);

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
    // translators: %s is the automation name
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

  const message = __('1 automation moved to the Trash.', 'mailpoet');
  void createSuccessNotice(message, {
    id: `workflow-trashed-${workflow.id}`,
    __unstableHTML: (
      <p>
        {message}{' '}
        <UndoTrashButton workflow={workflow} previousStatus={workflow.status} />
      </p>
    ),
  });

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

  void removeNotice(`workflow-trashed-${workflow.id}`);

  const message = __('1 automation restored from the Trash.', 'mailpoet');
  void createSuccessNotice(message, {
    __unstableHTML: (
      <p>
        {message}{' '}
        <EditWorkflow
          workflow={workflow}
          label={__('Edit automation', 'mailpoet')}
        />
      </p>
    ),
  });

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

import { apiFetch } from '@wordpress/data-controls';
import { Workflow, WorkflowStatus } from '../workflow';

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

  return {
    type: 'DELETE_WORKFLOW',
    workflow,
  } as const;
}

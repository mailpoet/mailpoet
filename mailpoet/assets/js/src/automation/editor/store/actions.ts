import { select } from '@wordpress/data';
import { apiFetch } from '@wordpress/data-controls';
import { store as interfaceStore } from '@wordpress/interface';
import { store as preferencesStore } from '@wordpress/preferences';
import { storeName } from './constants';
import { Feature, State } from './types';

export const openSidebar =
  (key) =>
  ({ registry }) =>
    registry.dispatch(interfaceStore).enableComplementaryArea(storeName, key);

export const closeSidebar =
  () =>
  ({ registry }) =>
    registry.dispatch(interfaceStore).disableComplementaryArea(storeName);

export const toggleFeature =
  (feature: Feature) =>
  ({ registry }) =>
    registry.dispatch(preferencesStore).toggle(storeName, feature);

export function toggleInserterSidebar() {
  return {
    type: 'TOGGLE_INSERTER_SIDEBAR',
  } as const;
}

export function setInserterPopover(data?: State['inserterPopover']) {
  return {
    type: 'SET_INSERTER_POPOVER',
    data,
  } as const;
}

export function selectStep(value) {
  return {
    type: 'SET_SELECTED_STEP',
    value,
  } as const;
}

export function setWorkflowName(name) {
  const workflow = select(storeName).getWorkflowData();
  workflow.name = name;
  return {
    type: 'UPDATE_WORKFLOW',
    workflow,
  } as const;
}

export function* save() {
  const workflow = select(storeName).getWorkflowData();
  const data = yield apiFetch({
    path: `/workflows/${workflow.id}`,
    method: 'PUT',
    data: { ...workflow },
  });

  return {
    type: 'SAVE',
    workflow: data?.data ?? workflow,
  } as const;
}

export function* activate() {
  const workflow = select(storeName).getWorkflowData();
  const data = yield apiFetch({
    path: `/workflows/${workflow.id}`,
    method: 'PUT',
    data: {
      ...workflow,
      status: 'active',
    },
  });

  return {
    type: 'ACTIVATE',
    workflow: data?.data ?? workflow,
  } as const;
}

export function registerStepType(stepType) {
  return {
    type: 'REGISTER_STEP_TYPE',
    stepType,
  };
}

export function updateStepArgs(stepId, name, value) {
  return {
    type: 'UPDATE_STEP_ARGS',
    stepId,
    name,
    value,
  };
}

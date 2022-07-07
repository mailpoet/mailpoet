import { select } from '@wordpress/data';
import { apiFetch } from '@wordpress/data-controls';
import { store as interfaceStore } from '@wordpress/interface';
import { store as preferencesStore } from '@wordpress/preferences';
import { storeName } from './constants';
import { Feature } from './types';

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

export function setInserterPopoverAnchor(anchor?: HTMLElement) {
  return {
    type: 'SET_INSERTER_POPOVER_ANCHOR',
    anchor,
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

export function* activate() {
  const workflow = select(storeName).getWorkflowData();
  const data = yield apiFetch({
    path: `/workflows/${workflow.id}`,
    method: 'PUT',
    data: {
      name: workflow.name,
      status: 'active',
    },
  });

  return {
    type: 'ACTIVATE',
    workflow: data.data,
  } as const;
}

export function registerStepType(stepType) {
  return {
    type: 'REGISTER_STEP_TYPE',
    stepType,
  };
}

import { createRegistrySelector } from '@wordpress/data';
import { store as interfaceStore } from '@wordpress/interface';
import { store as preferencesStore } from '@wordpress/preferences';
import { storeName } from './constants';
import { Context, Feature, State, StepType } from './types';
import { Item } from '../components/inserter/item';
import { Step, Workflow } from '../components/workflow/types';

export const isFeatureActive = createRegistrySelector(
  (select) =>
    (_, feature: Feature): boolean =>
      select(preferencesStore).get(storeName, feature),
);

export const isSidebarOpened = createRegistrySelector(
  (select) => (): boolean =>
    !!select(interfaceStore).getActiveComplementaryArea(storeName),
);

export function isInserterSidebarOpened(state: State): boolean {
  return state.inserterSidebar.isOpened;
}

export function getContext(state: State): Context {
  return state.context;
}

export function getContextStep(
  state: State,
  key: string,
): Context['steps'][number] | undefined {
  return state.context.steps[key];
}

export function getSteps(state: State): StepType[] {
  return Object.values(state.stepTypes);
}

export function getInserterActionSteps(state: State): Item[] {
  return Object.values(state.stepTypes).filter(
    ({ group }) => group === 'actions',
  );
}

export function getInserterLogicalSteps(state: State): Item[] {
  return Object.values(state.stepTypes).filter(
    ({ group }) => group === 'logical',
  );
}

export function getInserterPopover(
  state: State,
): State['inserterPopover'] | undefined {
  return state.inserterPopover;
}

export function getWorkflowData(state: State): Workflow {
  return state.workflowData;
}

export function getWorkflowSaved(state: State): boolean {
  return state.workflowSaved;
}

export function getSelectedStep(state: State): Step | undefined {
  return state.selectedStep;
}

export function getStepType(state: State, key: string): StepType | undefined {
  return state.stepTypes[key] ?? undefined;
}

export function getSelectedStepType(state: State): StepType | undefined {
  return getStepType(state, state.selectedStep?.key);
}

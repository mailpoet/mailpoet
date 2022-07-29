import { createRegistrySelector } from '@wordpress/data';
import { store as interfaceStore } from '@wordpress/interface';
import { store as preferencesStore } from '@wordpress/preferences';
import { storeName } from './constants';
import { Feature, State, StepType } from './types';
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

export function getInserterPopoverAnchor(
  state: State,
): HTMLElement | undefined {
  return state.inserterPopover.anchor;
}

export function getWorkflowData(state: State): Workflow {
  return state.workflowData;
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

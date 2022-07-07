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
  return state.inserter.actionSteps;
}

export function getInserterLogicalSteps(state: State): Item[] {
  return state.inserter.logicalSteps;
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

export function getSelectedStepType(state: State): StepType | undefined {
  return state.stepTypes[state.selectedStep?.key] ?? undefined;
}

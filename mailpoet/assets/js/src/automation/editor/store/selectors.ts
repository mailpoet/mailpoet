import { createRegistrySelector } from '@wordpress/data';
import { store as interfaceStore } from '@wordpress/interface';
import { store as preferencesStore } from '@wordpress/preferences';
import { storeName } from './constants';
import { Context, Errors, Feature, State, StepErrors, StepType } from './types';
import { Item } from '../components/inserter/item';
import { Step, Automation } from '../components/automation/types';

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

export function isActivationPanelOpened(state: State): boolean {
  return state.activationPanel.isOpened;
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

export function getAutomationData(state: State): Automation {
  return state.automationData;
}

export function getAutomationSaved(state: State): boolean {
  return state.automationSaved;
}

export function getSelectedStep(state: State): Step | undefined {
  return state.selectedStep;
}

export function getStepById(state: State, id: string): Step | undefined {
  return state.automationData.steps[id] ?? undefined;
}

export function getStepType(state: State, key: string): StepType | undefined {
  return state.stepTypes[key] ?? undefined;
}

export function getSelectedStepType(state: State): StepType | undefined {
  return getStepType(state, state.selectedStep?.key);
}

export function getErrors(state: State): Errors | undefined {
  return state.errors;
}

export function getStepError(state: State, id: string): StepErrors | undefined {
  return state.errors?.steps[id] ?? undefined;
}

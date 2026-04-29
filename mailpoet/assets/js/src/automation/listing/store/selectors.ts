import { AutomationItem, State } from './types';
import { automationCount, legacyAutomationCount } from '../../config';

export function getAutomations(state: State): AutomationItem[] | undefined {
  return state.automations;
}

export function getLegacyAutomations(
  state: State,
): AutomationItem[] | undefined {
  return state.legacyAutomations;
}

export function getAllAutomations(state: State): AutomationItem[] | undefined {
  return state.automations && state.legacyAutomations
    ? [...state.automations, ...state.legacyAutomations]
    : undefined;
}

export function getAutomationCount(state: State): number {
  return state.automations && state.legacyAutomations
    ? state.automations.length + state.legacyAutomations.length
    : automationCount + legacyAutomationCount;
}

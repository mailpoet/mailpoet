import { State } from './types';
import { Automation } from '../automation';
import { automationCount, legacyAutomationCount } from '../../config';

export function getAutomations(state: State): Automation[] {
  return state.automations;
}

export function getLegacyAutomations(state: State): Automation[] {
  return state.legacyAutomations;
}

export function getAllAutomations(state: State): Automation[] {
  return state.automations && state.legacyAutomations
    ? [...state.automations, ...state.legacyAutomations]
    : undefined;
}

export function getAutomationCount(state: State): number {
  return state.automations && state.legacyAutomations
    ? state.automations.length + state.legacyAutomations.length
    : automationCount + legacyAutomationCount;
}

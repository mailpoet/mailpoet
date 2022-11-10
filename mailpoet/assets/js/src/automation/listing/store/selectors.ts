import { State } from './types';
import { Automation } from '../automation';
import { automationCount } from '../../config';

export function getAutomations(state: State): Automation[] {
  return state.automations;
}

export function getAutomationCount(state: State): number {
  return state.automations ? state.automations.length : automationCount;
}

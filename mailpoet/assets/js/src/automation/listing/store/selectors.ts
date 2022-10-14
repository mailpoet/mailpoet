import { State } from './types';
import { Workflow } from '../workflow';

export function getWorkflows(state: State): Workflow[] {
  return state.workflows;
}

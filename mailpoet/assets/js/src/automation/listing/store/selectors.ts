import { State } from './types';
import { Workflow } from '../workflow';
import { workflowCount } from '../../config';

export function getWorkflows(state: State): Workflow[] {
  return state.workflows;
}

export function getWorkflowCount(state: State): number {
  return state.workflows ? state.workflows.length : workflowCount;
}

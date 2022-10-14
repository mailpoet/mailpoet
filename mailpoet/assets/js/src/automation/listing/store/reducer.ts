import { Action } from '@wordpress/data';
import { State } from './types';
import { Workflow } from '../workflow';

export function reducer(state: State, action: Action): State {
  switch (action.type) {
    case 'SET_WORKFLOWS':
      return {
        ...state,
        workflows: action.workflows,
      };
    case 'ADD_WORKFLOW':
      return {
        ...state,
        workflows: [action.workflow, ...state.workflows],
      };
    case 'UPDATE_WORKFLOW':
      return {
        ...state,
        workflows: state.workflows.map((workflow: Workflow) =>
          workflow.id === action.workflow.id ? action.workflow : workflow,
        ),
      };
    case 'DELETE_WORKFLOW':
      return {
        ...state,
        workflows: state.workflows.filter(
          (workflow: Workflow) => workflow.id !== action.workflow.id,
        ),
      };
    default:
      return state;
  }
}

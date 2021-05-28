import { assign } from 'lodash/fp';
import {
  Actions,
  ActionType,
  SetSegmentActionType,
  SetErrorsActionType,
  StateType,
} from '../types';

function setSegment(state: StateType, action: SetSegmentActionType): StateType {
  return {
    ...state,
    segment: action.segment,
  };
}

function setErrors(state: StateType, action: SetErrorsActionType): StateType {
  return {
    ...state,
    errors: action.errors,
  };
}

function updateSegment(state: StateType, action: SetSegmentActionType): StateType {
  const oldSegment = state.segment;
  return {
    ...state,
    segment: assign(oldSegment, action.segment),
  };
}

export const createReducer = (defaultState: StateType) => (
  state: StateType = defaultState,
  action: ActionType
): StateType => {
  switch (action.type) {
    case Actions.SET_SEGMENT: return setSegment(state, action as SetSegmentActionType);
    case Actions.SET_ERRORS: return setErrors(state, action as SetErrorsActionType);
    case Actions.UPDATE_SEGMENT: return updateSegment(state, action as SetSegmentActionType);
    default:
      return state;
  }
};

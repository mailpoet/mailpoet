import { assign } from 'lodash/fp';
import {
  Actions,
  ActionType,
  SetDynamicSegmentsActionType,
  SetSegmentActionType,
  SetErrorsActionType,
  SetSegmentFilerActionType,
  SetSubscriberCountActionType,
  StateType,
  SetPreviousPageActionType,
} from '../types';
import { getSegmentInitialState } from './initial-state';

function setDynamicSegments(
  state: StateType,
  action: SetDynamicSegmentsActionType,
): StateType {
  return {
    ...state,
    dynamicSegments: action.dynamicSegments,
  };
}

function setSegment(state: StateType, action: SetSegmentActionType): StateType {
  return {
    ...state,
    segment: action.segment,
  };
}

function resetSegmentAndErrors(state: StateType): StateType {
  return {
    ...state,
    segment: getSegmentInitialState(),
    errors: [],
  };
}

function setErrors(state: StateType, action: SetErrorsActionType): StateType {
  return {
    ...state,
    errors: action.errors,
  };
}

function updateSegment(
  state: StateType,
  action: SetSegmentActionType,
): StateType {
  const oldSegment = state.segment;
  return {
    ...state,
    segment: assign(oldSegment, action.segment),
  };
}

function updateSegmentFilter(
  state: StateType,
  action: SetSegmentFilerActionType,
): StateType {
  const segment = { ...state.segment };
  segment.filters[action.filterIndex] = assign(
    segment.filters[action.filterIndex],
    action.filter,
  );
  return {
    ...state,
    segment,
  };
}

function updateSubscriberCount(
  state: StateType,
  action: SetSubscriberCountActionType,
): StateType {
  const oldCount = state.subscriberCount;
  return {
    ...state,
    subscriberCount: assign(oldCount, action.subscriberCount),
  };
}

function setPreviousPage(
  state: StateType,
  action: SetPreviousPageActionType,
): StateType {
  return {
    ...state,
    previousPage: action.previousPage,
  };
}

export const createReducer =
  (defaultState: StateType) =>
  (
    state: StateType = defaultState, // eslint-disable-line @typescript-eslint/default-param-last
    action: ActionType,
  ): StateType => {
    switch (action.type) {
      case Actions.SET_DYNAMIC_SEGMENTS:
        return setDynamicSegments(
          state,
          action as SetDynamicSegmentsActionType,
        );
      case Actions.SET_SEGMENT:
        return setSegment(state, action as SetSegmentActionType);
      case Actions.RESET_SEGMENT_AND_ERRORS:
        return resetSegmentAndErrors(state);
      case Actions.SET_ERRORS:
        return setErrors(state, action as SetErrorsActionType);
      case Actions.UPDATE_SEGMENT:
        return updateSegment(state, action as SetSegmentActionType);
      case Actions.UPDATE_SEGMENT_FILTER:
        return updateSegmentFilter(state, action as SetSegmentFilerActionType);
      case Actions.UPDATE_SUBSCRIBER_COUNT:
        return updateSubscriberCount(
          state,
          action as SetSubscriberCountActionType,
        );
      case Actions.SET_PREVIOUS_PAGE:
        return setPreviousPage(state, action as SetPreviousPageActionType);
      default:
        return state;
    }
  };

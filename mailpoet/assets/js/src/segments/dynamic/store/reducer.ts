import { assign } from 'lodash/fp';
import {
  Actions,
  ActionType,
  SetSegmentActionType,
  SetErrorsActionType,
  SetSegmentFilerActionType,
  SetSubscriberCountActionType,
  StateType,
  SetPreviousPageActionType,
  UpdateDynamicSegmentsQueryActionType,
  SelectDynamicSegmentActionType,
  SetDynamicSegmentsActionType,
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

function setAllDynamicSegmentsSelected(state: StateType): StateType {
  const data = state.dynamicSegments.data.map((segment) => ({
    ...segment,
    selected: true,
  }));
  return {
    ...state,
    dynamicSegments: {
      ...state.dynamicSegments,
      data: [...data],
    },
  };
}

function setAllDynamicSegmentsUnselected(state: StateType): StateType {
  const data = state.dynamicSegments.data.map((segment) => ({
    ...segment,
    selected: false,
  }));
  return {
    ...state,
    dynamicSegments: {
      ...state.dynamicSegments,
      data: [...data],
    },
  };
}

function setSelectDynamicSegment(
  state: StateType,
  action: SelectDynamicSegmentActionType,
): StateType {
  const data = state.dynamicSegments.data;
  const index = data.findIndex((segment) => segment.id === action.segment.id);
  data.splice(index, 1, {
    ...action.segment,
    selected: true,
  });
  return {
    ...state,
    dynamicSegments: {
      ...state.dynamicSegments,
      data: [...data],
    },
  };
}
function setUnselectDynamicSegment(
  state: StateType,
  action: SelectDynamicSegmentActionType,
): StateType {
  const data = state.dynamicSegments.data;
  const index = data.findIndex((segment) => segment.id === action.segment.id);
  data.splice(index, 1, {
    ...action.segment,
    selected: false,
  });
  return {
    ...state,
    dynamicSegments: {
      ...state.dynamicSegments,
      data: [...data],
    },
  };
}

function setSegment(state: StateType, action: SetSegmentActionType): StateType {
  return {
    ...state,
    segment: action.segment,
  };
}

function setDynamicSegmentsQuery(
  state: StateType,
  action: UpdateDynamicSegmentsQueryActionType,
): StateType {
  return {
    ...state,
    dynamicSegmentsQuery: action.query,
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
      case Actions.UNSELECT_ALL_DYNAMIC_SEGMENTS:
        return setAllDynamicSegmentsUnselected(state);
      case Actions.SELECT_ALL_DYNAMIC_SEGMENTS:
        return setAllDynamicSegmentsSelected(state);
      case Actions.SET_DYNAMIC_SEGMENTS:
        return setDynamicSegments(
          state,
          action as SetDynamicSegmentsActionType,
        );
      case Actions.SELECT_DYNAMIC_SEGMENT:
        return setSelectDynamicSegment(
          state,
          action as SelectDynamicSegmentActionType,
        );
      case Actions.UNSELECT_DYNAMIC_SEGMENT:
        return setUnselectDynamicSegment(
          state,
          action as SelectDynamicSegmentActionType,
        );
      case Actions.UPDATE_DYNAMIC_SEGMENTS_QUERY:
        return setDynamicSegmentsQuery(
          state,
          action as UpdateDynamicSegmentsQueryActionType,
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

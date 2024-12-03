import { ACTION_TYPES as types } from './action-types';
// Thunks are functions that can be dispatched, similar to actions creators
export * from './thunks';

/**
 * An action creator that dispatches the plain action responsible for setting the newsletter data in the store.
 */
export function receiveNewsletters( newsletters ) {
  return {
    type: types.RECEIVE_NEWSLETTERS,
    response: newsletters,
  };
}

/**
 * An action creator that dispatches the plain action responsible for setting the error in the store.
 */
export function receiveError( error ) { 
  return {
    type: types.RECEIVE_ERROR,
    error,
  };
}

export function fetchNewslettersRequest() {
  return {
    type: types.FETCH_NEWSLETTERS_REQUEST,
  };
}

export function fetchNewslettersSuccess() {
  return {
    type: types.FETCH_NEWSLETTERS_SUCCESS,
  };
}

export function fetchNewslettersFailure() {
  return {
    type: types.FETCH_NEWSLETTERS_FAILURE,
  };
}
import { ACTION_TYPES as types } from './action-types';
// Thunks are functions that can be dispatched, similar to actions creators
export * from './thunks';

/**
 * An action creator that dispatches the plain action responsible for setting the newsletter data in the store.
 */

export function startStandardRequest() {
	return {
	  type: types.START_STANDARD_REQUEST,
	};
 }

export function receiveStandardNewsletters( newsletters ) {
  return {
    type: types.RECEIVE_STANDARD_NEWSLETTERS,
    response: newsletters,
  };
}

  export function finishStandardRequest() {
	return {
	  type: types.FINISH_STANDARD_REQUEST,
	};
  }  

 export function startPostNotificationRequest() {
	return {
	  type: types.START_POST_NOTIFICATION_REQUEST,
	};
 }
export function receivePostNofifications( newsletters ) {
	return {
	  type: types.RECEIVE_POST_NOTIFICATIONS,
	  response: newsletters,
	};
}
export function finishPostNotificationsRequest() {
	return {
	  type: types.FINISH_POST_NOTIFICATION_REQUEST,
	};
  }  

export function startReEngagementRequest() {
	return {
	  type: types.START_RE_ENGAGEMENT_REQUEST,
	};
}

export function receiveReEngament( newsletters ) {
	return {
	  type: types.RECEIVE_RE_ENGAMENT,
	  response: newsletters,
	};
}

export function finishReEngagementRequest() {
	return {
	  type: types.FINISH_RE_ENGAGEMENT_REQUEST,
	};
}  

export function receiveError( error ) { 
  return {
    type: types.RECEIVE_ERROR,
    error: error,
  };
}

export function receiveMeta( meta ) {
	return {
	  type: types.RECEIVE_META,
	  response: meta,
	};
}


export function startDuplicateRequest() {
	return {
		type: types.DUPLICATE_NEWSLETTER_REQUEST_START,
	};
}

export function receiveDuplicatedNewsletter(newsletter) {
	return {
		type: types.DUPLICATE_NEWSLETTER_REQUEST_SUCCESS,
		payload: newsletter
	};
}
  
export function finishDuplicateRequest() {
	return {
		type: types.DUPLICATE_NEWSLETTER_REQUEST_FINISH,
	};
}
  
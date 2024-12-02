
import { ACTION_TYPES as types } from './action-types';
// Thunks are functions that can be dispatched, similar to actions creators
//export * from './thunks';

/**
 * An action creator that dispatches the plain action responsible for setting the newsletter data in the store.
 */
export function setNewsletterData( newsletters ) {
	return {
		type: types.SET_NEWSLETTER_DATA,
		response: newsletters,
	};
}
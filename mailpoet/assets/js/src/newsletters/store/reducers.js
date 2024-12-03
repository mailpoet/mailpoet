import { ACTION_TYPES as types } from './action-types';
import { defaultNewsletterState } from './default-state';
import { EMPTY_NEWSLETTER_ERRORS } from './constants';

const reducer = ( state = defaultNewsletterState, action ) => {
    console.log('reducer', state, action);
	switch ( action.type ) {
		case types.RECEIVE_NEWSLETTERS:
			if ( action.response ) {
				return {
                    ...state,
                    newsletterListing: action.response,
                    isFetching: false,
                };
			}
			break;
		default:
			break;
		}
	return state;
};

export default reducer;
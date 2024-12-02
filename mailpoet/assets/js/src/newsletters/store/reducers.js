import { ACTION_TYPES as types } from './action-types';
import { defaultNewsletterState } from './default-state';
import { EMPTY_NEWSLETTER_ERRORS } from './constants';

const reducer = ( state = defaultNewsletterState, action ) => {
    console.log('reducer', state, action);
	switch ( action.type ) {
		case types.SET_NEWSLETTER_DATA:
			if ( action.response ) {
				return {
                    ...state,
                    list: [...state.list, ...action.response],
                    isFetching: false,
                };
			}
			break;}
	return state;
};

export default reducer;
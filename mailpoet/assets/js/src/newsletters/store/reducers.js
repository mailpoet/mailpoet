import { ACTION_TYPES as types } from './action-types';
import { defaultNewsletterState } from './default-state';
import { EMPTY_NEWSLETTER_ERRORS } from './constants';

const reducer = (state = defaultNewsletterState, action) => {
  switch (action.type) {
    case types.RECEIVE_NEWSLETTERS:
      if (action.response) {
        return {
          ...state,
          newsletterListing: action.response,
        };
      }
      break;
    case types.RECEIVE_ERROR:
      return {
        ...state,
        errors: [...state.errors, action.error],
        isLoading: false,
      };
    case types.FETCH_NEWSLETTERS_REQUEST:
      return {
        ...state,
        isLoading: true,
      };
    case types.FETCH_NEWSLETTERS_SUCCESS:
    case types.FETCH_NEWSLETTERS_FAILURE:
      return {
        ...state,
        isLoading: false,
      };
    default:
      break;
  }
  return state;
};

export default reducer;
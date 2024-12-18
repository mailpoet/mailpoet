import { NewsletterActionTypes as Actions, NewsletterActions, NewsletterState } from './types';
import { defaultNewsletterState } from './default-state';

const reducer = (state: NewsletterState = defaultNewsletterState, action: NewsletterActions): NewsletterState => {
  switch (action.type) {
    case Actions.START_STANDARD_REQUEST:
      return {
        ...state,
        isLoading: { ...state.isLoading, standard: true },
      };
    case Actions.RECEIVE_STANDARD_NEWSLETTERS:
      return {
        ...state,
        newsletters: { ...state.newsletters, standard: action.response },
      };
    case Actions.FINISH_STANDARD_REQUEST:
      return {
        ...state,
        isLoading: { ...state.isLoading, standard: false },
      };
    case Actions.START_POST_NOTIFICATION_REQUEST:
      return {
        ...state,
        isLoading: { ...state.isLoading, postNotification: true },
      };
    case Actions.RECEIVE_POST_NOTIFICATIONS:
      return {
        ...state,
        newsletters: { ...state.newsletters, postNotifications: action.response },
      };
    case Actions.FINISH_POST_NOTIFICATION_REQUEST:
      return {
        ...state,
        isLoading: { ...state.isLoading, postNotification: false },
      };
    case Actions.START_RE_ENGAGEMENT_REQUEST:
      return {
        ...state,
        isLoading: { ...state.isLoading, reEngagement: true },
      };
    case Actions.RECEIVE_RE_ENGAGEMENT:
		return {
			...state,
			newsletters: { ...state.newsletters, reEngagements: action.response },
			};
	case Actions.FINISH_RE_ENGAGEMENT_REQUEST:
		return {
			...state,
			isLoading: { ...state.isLoading, reEngagement: false },
		};					  		
    case Actions.RECEIVE_ERROR:
      return {
        ...state,
        errors: [...state.errors, action.error],
      };    
	case Actions.RECEIVE_META:
	  return {
		...state,
		meta: action.response,
	};
    case Actions.DUPLICATE_NEWSLETTER_REQUEST_START:
      return {
        ...state,
        isLoading: { ...state.isLoading, duplication: true },
      };

    case Actions.DUPLICATE_NEWSLETTER_REQUEST_SUCCESS:
      return {
        ...state,
        newsletters: {
          ...state.newsletters,
          standard: [action.payload, ...state.newsletters.standard],
        },
      };

    case Actions.DUPLICATE_NEWSLETTER_REQUEST_FINISH:
      return {
        ...state,
        isLoading: { ...state.isLoading, duplication: false },
      };
    default:
      return state;
  }
};

export default reducer;
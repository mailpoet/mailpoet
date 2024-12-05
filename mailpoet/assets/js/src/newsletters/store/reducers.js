import { ACTION_TYPES as types } from './action-types';
import { defaultNewsletterState } from './default-state';


const reducer = (state = defaultNewsletterState, action) => {
  switch (action.type) {
    case types.START_STANDARD_REQUEST:
      return {
        ...state,
        isLoading: { ...state.isLoading, standard: true },
      };	
    case types.RECEIVE_STANDARD_NEWSLETTERS:
	  return {
		...state,
		newsletters: { ...state.newsletters, standard: action.response },
	  };
	case types.RECEIVE_STANDARD_SEGMENTS:
	  return {
		...state,
		newsletters: { ...state.newsletters, standardSegments: action.response },
	  };
	  case types.FINISH_STANDARD_REQUEST:
		return {
		  ...state,
		  isLoading: { ...state.isLoading, standard: false },
		};		  
	  case types.START_POST_NOTIFICATION_REQUEST:
		return {
			...state,
			isLoading: { ...state.isLoading, postNotification: true },
		};  	  	  
	 case types.RECEIVE_POST_NOTIFICATIONS:
	   return {
		  ...state,
		  newsletters: { ...state.newsletters, postNotifications: action.response },
		};
	case types.FINISH_POST_NOTIFICATION_REQUEST:
		return {
			...state,
			isLoading: { ...state.isLoading, postNotification: false },
		};		

	case types.START_RE_ENGAGEMENT_REQUEST:
		return {
			...state,
			isLoading: { ...state.isLoading, reEngagement: true },
		};
	case types.RECEIVE_RE_ENGAMENT:
		return {
			...state,
			newsletters: { ...state.newsletters, postNotificareEngagementtions: action.response },
			};
	case types.FINISH_RE_ENGAGEMENT_REQUEST:
		return {
			...state,
			isLoading: { ...state.isLoading, reEngagement: false },
		};					  		
    case types.RECEIVE_ERROR:
      return {
        ...state,
        errors: [...state.errors, action.error],
      };    

    default:
      break;
  }
  return state;
};

export default reducer;
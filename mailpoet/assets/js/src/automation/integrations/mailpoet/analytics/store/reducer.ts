import { State } from './types';

export function reducer(state: State, action): State {
  switch (action.type) {
    case 'SET_QUERY':
      return {
        ...state,
        query: action.payload,
      };
    case 'SET_SECTION_DATA':
      return {
        ...state,
        sections: {
          ...state.sections,
          [action.payload.id]: action.payload,
        },
      };
    case 'OPEN_PREMIUM_MODAL':
      return {
        ...state,
        premiumModal: {
          content: action.content,
          utmCampaign: action.utmCampaign,
          data: action.data,
        },
      };
    case 'CLOSE_PREMIUM_MODAL':
      return {
        ...state,
        premiumModal: undefined,
      };
    default:
      return state;
  }
}

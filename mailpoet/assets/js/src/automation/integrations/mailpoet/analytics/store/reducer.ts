import { State, SubscriberSection } from './types';

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
    case 'UPDATE_RUN_STATUS': {
      const subscribersSection = state.sections
        .subscribers as SubscriberSection;
      if (!subscribersSection?.data?.items) {
        return state;
      }

      const updatedItems = subscribersSection.data.items.map((item) => {
        if (item.run.id === action.payload.runId) {
          return {
            ...item,
            run: {
              ...item.run,
              status: action.payload.status,
            },
          };
        }
        return item;
      });

      return {
        ...state,
        sections: {
          ...state.sections,
          subscribers: {
            ...subscribersSection,
            data: {
              ...subscribersSection.data,
              items: updatedItems,
            },
          },
        },
      };
    }
    default:
      return state;
  }
}

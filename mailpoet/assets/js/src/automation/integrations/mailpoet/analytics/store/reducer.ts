import { State, SubscriberSection } from './types';

export function reducer(state: State, action): State {
  switch (action.type) {
    case 'RUN_STATUS_UPDATE_REQUEST': {
      const { runId, status } = action.payload;
      return {
        ...state,
        runStatusUpdates: {
          ...state.runStatusUpdates,
          [runId]: { status },
        },
      };
    }
    case 'RUN_STATUS_UPDATE_FAILURE': {
      const { [action.payload.runId]: removedUpdate, ...rest } =
        state.runStatusUpdates;
      return {
        ...state,
        runStatusUpdates: rest,
      };
    }
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
        const { [action.payload.runId]: removedUpdate, ...rest } =
          state.runStatusUpdates;
        return {
          ...state,
          runStatusUpdates: rest,
        };
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
        runStatusUpdates: (() => {
          const { [action.payload.runId]: removedUpdate, ...rest } =
            state.runStatusUpdates;
          return rest;
        })(),
      };
    }
    default:
      return state;
  }
}

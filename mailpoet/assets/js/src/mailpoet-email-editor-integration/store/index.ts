import { createReduxStore, register } from '@wordpress/data';

export const STORE_NAME = 'mailpoet/email-editor';

type State = {
  reviewPanel: {
    isOpen: boolean;
  };
};

const DEFAULT_STATE: State = {
  reviewPanel: {
    isOpen: false,
  },
};

const actions = {
  openReviewPanel: () => ({ type: 'OPEN_REVIEW_PANEL' as const }),
  closeReviewPanel: () => ({ type: 'CLOSE_REVIEW_PANEL' as const }),
  toggleReviewPanel: () => ({ type: 'TOGGLE_REVIEW_PANEL' as const }),
};

type Action = ReturnType<typeof actions[keyof typeof actions]>;

function reducer(state: State | undefined, action: Action): State {
  const currentState = state ?? DEFAULT_STATE;
  switch (action.type) {
    case 'OPEN_REVIEW_PANEL':
      return { ...currentState, reviewPanel: { isOpen: true } };
    case 'CLOSE_REVIEW_PANEL':
      return { ...currentState, reviewPanel: { isOpen: false } };
    case 'TOGGLE_REVIEW_PANEL':
      return {
        ...currentState,
        reviewPanel: { isOpen: !currentState.reviewPanel.isOpen },
      };
    default:
      return currentState;
  }
}

const selectors = {
  isReviewPanelOpen: (state: State) => state.reviewPanel.isOpen,
};

export const store = createReduxStore(STORE_NAME, {
  reducer,
  actions,
  selectors,
});

register(store);

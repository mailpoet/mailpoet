import { createReduxStore, register } from '@wordpress/data';

export const STORE_NAME = 'mailpoet/email-editor';

type State = {
  sendPanel: {
    isOpen: boolean;
  };
};

const DEFAULT_STATE: State = {
  sendPanel: {
    isOpen: false,
  },
};

const actions = {
  openSendPanel: () => ({ type: 'OPEN_SEND_PANEL' as const }),
  closeSendPanel: () => ({ type: 'CLOSE_SEND_PANEL' as const }),
  toggleSendPanel: () => ({ type: 'TOGGLE_SEND_PANEL' as const }),
};

type Action = ReturnType<typeof actions[keyof typeof actions]>;

function reducer(state: State | undefined, action: Action): State {
  const currentState = state ?? DEFAULT_STATE;
  switch (action.type) {
    case 'OPEN_SEND_PANEL':
      return { ...currentState, sendPanel: { isOpen: true } };
    case 'CLOSE_SEND_PANEL':
      return { ...currentState, sendPanel: { isOpen: false } };
    case 'TOGGLE_SEND_PANEL':
      return {
        ...currentState,
        sendPanel: { isOpen: !currentState.sendPanel.isOpen },
      };
    default:
      return currentState;
  }
}

const selectors = {
  isSendPanelOpen: (state: State) => state.sendPanel.isOpen,
};

export const store = createReduxStore(STORE_NAME, {
  reducer,
  actions,
  selectors,
});

register(store);

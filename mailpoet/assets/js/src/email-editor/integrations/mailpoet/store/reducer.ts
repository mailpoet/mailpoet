import { State } from './types';

export function reducer(state: State, action): State {
  switch (action.type) {
    case 'UPDATE_PREVIEW_TO_EMAIL':
      return {
        ...state,
        previewToEmail: action.previewToEmail,
      };
    case 'SET_IS_SENDING_PREVIEW_EMAIL':
      return {
        ...state,
        isSendingPreviewEmail: action.isSendingPreviewEmail,
      };
    case 'SET_SENDING_PREVIEW_STATUS':
      return {
        ...state,
        sendingPreviewStatus: action.status,
      };

    default:
      return state;
  }
}

import { State } from './types';

export function reducer(state: State, action): State {
  switch (action.type) {
    case 'UPDATE_PREVIEW_TO_EMAIL':
      return {
        ...state,
        previewToEmail: action.previewToEmail,
      };

    default:
      return state;
  }
}

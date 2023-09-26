import { State } from './types';

export function getSavedState(state: State): State['savedState'] {
  return state.savedState;
}

export function getPreviewToEmail(state: State): string {
  return state.previewToEmail;
}

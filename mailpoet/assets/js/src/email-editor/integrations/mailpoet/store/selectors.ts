import { State } from './types';

export function getSavedState(state: State): State['savedState'] {
  return state.savedState;
}

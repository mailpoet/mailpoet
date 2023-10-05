import { SendingPreviewStatus, State } from './types';

export function getSavedState(state: State): State['savedState'] {
  return state.savedState;
}

export function getPreviewToEmail(state: State): string {
  return state.previewToEmail;
}

export function getIsSendingPreviewEmail(state: State): boolean {
  return state.isSendingPreviewEmail;
}

export function getSendingPreviewStatus(
  state: State,
): SendingPreviewStatus | null {
  return state.sendingPreviewStatus;
}

import { Action, KeyActivationState } from '../types';

export function updateKeyActivationState(
  fields: Partial<KeyActivationState>,
): Action {
  return { type: 'UPDATE_KEY_ACTIVATION_STATE', fields };
}

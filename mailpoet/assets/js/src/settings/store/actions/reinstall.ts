import { MailPoet } from 'mailpoet';

export function* reinstall() {
  MailPoet.Modal.loading(true);
  const { success, error } = yield {
    type: 'CALL_API',
    endpoint: 'setup',
    action: 'reset',
  };
  MailPoet.Modal.loading(false);
  if (!success) {
    return { type: 'SAVE_FAILED', error };
  }
  yield { type: 'TRACK_REINSTALLED' };
  return { type: 'SAVE_DONE' };
}

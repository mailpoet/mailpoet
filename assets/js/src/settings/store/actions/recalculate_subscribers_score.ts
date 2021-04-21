import MailPoet from 'mailpoet';

export function* recalculateSubscribersScore(): Generator<{
  type: string;
  endpoint: string;
  action: string;
}> {
  MailPoet.Modal.loading(true);
  yield {
    type: 'CALL_API',
    endpoint: 'settings',
    action: 'recalculateSubscribersScore',
  };
  MailPoet.Modal.loading(false);
  return { type: 'SAVE_DONE' };
}

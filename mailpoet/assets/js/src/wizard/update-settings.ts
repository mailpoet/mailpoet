import { MailPoet } from 'mailpoet';

export async function updateSettings(data) {
  await MailPoet.Ajax.post({
    api_version: window.mailpoet_api_version,
    endpoint: 'settings',
    action: 'set',
    data,
  }).fail((response: ErrorResponse) => {
    if (response.errors.length > 0) {
      MailPoet.Notice.showApiErrorNotice(response, { scroll: true });
    }
  });
}

import { apiFetch } from '@wordpress/data-controls';

export function sendNewsletterPreview(newsletterId: number) {
  return {
    type: 'SEND_NEWSLETTER_PREVIEW',
    newsletterId,
  };
}

export function* requestSendingNewsletterPreview(
  newsletterId: number,
  email: string,
) {
  const url = window.mailpoet_json_api_root;
  const token = window.mailpoet_token;
  const method = 'POST';
  const body = new FormData();
  body.append('token', token);
  body.append('action', 'mailpoet');
  body.append('api_version', 'v1');
  body.append('endpoint', 'newsletters');
  body.append('method', 'sendPreview');
  body.append('data[subscriber]', email);
  body.append('data[id]', newsletterId.toString());
  const data = yield apiFetch({
    url,
    method,
    body,
  });

  const id: number = data?.data;
  yield {
    type: 'SEND_NEWSLETTER_PREVIEW',
    id,
  };
}

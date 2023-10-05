import { apiFetch } from '@wordpress/data-controls';
import { SendingPreviewStatus } from './types';

export function updatePreviewToEmail(previewToEmail: string) {
  return {
    type: 'UPDATE_PREVIEW_TO_EMAIL',
    previewToEmail,
  };
}

export function sendNewsletterPreview(newsletterId: number) {
  return {
    type: 'SEND_NEWSLETTER_PREVIEW',
    newsletterId,
  };
}

export function* requestSendingNewsletterPreview(
  newsletterId: number,
  email: string,
  callback: () => void,
) {
  try {
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
    const response = yield apiFetch({
      url,
      method,
      body,
    });

    const id: number = response?.data;
    yield {
      type: 'SEND_NEWSLETTER_PREVIEW',
      newsletterId: id,
    };
    yield {
      type: 'SET_SENDING_PREVIEW_STATUS',
      status: SendingPreviewStatus.SUCCESS,
    };
  } catch (errorResponse) {
    yield {
      type: 'SET_SENDING_PREVIEW_STATUS',
      status: SendingPreviewStatus.ERROR,
    };
  }
  callback();
}

export function setIsSendingPreviewEmail(isSendingPreviewEmail: boolean) {
  return {
    type: 'SET_IS_SENDING_PREVIEW_EMAIL',
    isSendingPreviewEmail,
  };
}

export function setSendingPreviewStatus(status: SendingPreviewStatus | null) {
  return {
    type: 'SET_SENDING_PREVIEW_STATUS',
    status,
  };
}

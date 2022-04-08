import { t } from 'common/functions';
import { Settings } from 'settings/store/types';

export default function* sendTestEmail(
  recipient: string,
  mailer: Settings['mta'],
) {
  if (!recipient) {
    return { type: 'TEST_EMAIL_FAILED', error: [t('cantSendEmail')] };
  }
  yield { type: 'START_TEST_EMAIL_SENDING' };
  const res = yield {
    type: 'CALL_API',
    endpoint: 'mailer',
    action: 'send',
    data: {
      mailer,
      newsletter: {
        subject: t('testEmailSubject'),
        body: {
          html: `<p>${t('testEmailBody')}</p>`,
          text: t('testEmailBody'),
        },
      },
      subscriber: recipient,
    },
  };
  yield {
    type: 'TRACK_TEST_EMAIL_SENT',
    success: res.success,
    method: mailer.method,
  };
  if (!res.success) return { type: 'TEST_EMAIL_FAILED', error: res.error };
  return { type: 'TEST_EMAIL_SUCCESS' };
}

import React from 'react';
import MailPoet from 'mailpoet';
import ReactStringReplace from 'react-string-replace';

import HelpTooltip from 'help-tooltip';
import { GlobalContext } from 'context';
import { t, onChange } from 'common/functions';
import { Label, Inputs } from 'settings/components';
import { useSetting } from 'settings/store/hooks';

export default function TestSending() {
  const [email, setEmail] = React.useState<string>((window as any).mailpoet_current_user_email);
  const [mailer] = useSetting('mta');
  const [fromAddress] = useSetting('sender', 'address');
  const { notices } = React.useContext<any>(GlobalContext);
  const sendTestEmail = async (recipient) => {
    if (!fromAddress) {
      return notices.error(<p>{t('cantSendEmail')}</p>, { scroll: true, static: true });
    }

    MailPoet.Modal.loading(true);
    try {
      await MailPoet.Ajax.post({
        api_version: (window as any).mailpoet_api_version,
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
      });
      notices.success(<p>{t('emailSent')}</p>, { scroll: true });
      trackTestEmailSent(mailer.method, true);
    } catch (res) {
      if (res.errors.length > 0) {
        notices.error(
          res.errors.map((err) => <p key={err.message}>{err.message}</p>),
          { scroll: true }
        );
      }
      trackTestEmailSent(mailer.method, false);
    }
    MailPoet.Modal.loading(false);
  };

  const trackTestEmailSent = (method, success) => {
    MailPoet.trackEvent(
      'User has sent a test email from Settings',
      {
        'Sending was successful': !!success,
        'Sending method type': method,
        'MailPoet Free version': (window as any).mailpoet_version,
      }
    );
  };

  return (
    <>
      <Label title={t('testSending')} htmlFor="mailpoet_mta_test_email" />
      <Inputs>
        <input
          type="text"
          className="regular-text"
          id="mailpoet_mta_test_email"
          value={email}
          onChange={onChange(setEmail)}
        />
        <button
          type="button"
          id="mailpoet_mta_test"
          className="button-secondary"
          onClick={() => sendTestEmail(email)}
        >
          {t('sendTestEmail')}
        </button>
        <HelpTooltip
          tooltipId="tooltip-settings-test"
          className="mailpoet_tooltip_icon"
          tooltip={(
            <span style={{ pointerEvents: 'all' }}>
              {ReactStringReplace(t('testEmailTooltip'), /\[link\](.*?)\[\/link\]/g,
                (match, i) => (
                  <a
                    key={i}
                    target="_blank"
                    rel="noopener noreferrer"
                    data-beacon-article="580846f09033604df5166ed1"
                    href="https://kb.mailpoet.com/article/146-my-newsletters-are-not-being-received"
                  >
                    {match}
                  </a>
                ))}
            </span>
          )}
        />
      </Inputs>
    </>
  );
}

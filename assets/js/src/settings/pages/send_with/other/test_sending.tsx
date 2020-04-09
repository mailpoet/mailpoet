import React from 'react';
import ReactStringReplace from 'react-string-replace';

import HelpTooltip from 'help-tooltip';
import { GlobalContext } from 'context';
import { t, onChange } from 'common/functions';
import { Label, Inputs } from 'settings/components';
import { useSetting, useAction } from 'settings/store/hooks';

export default function TestSending() {
  const [email, setEmail] = React.useState<string>((window as any).mailpoet_current_user_email);
  const [mailer] = useSetting('mta');
  const [fromAddress] = useSetting('sender', 'address');
  const { notices } = React.useContext<any>(GlobalContext);
  const sendTestEmail = useAction('sendTestEmail');

  const sendEmail = async () => {
    if (!fromAddress) {
      notices.error(<p>{t('cantSendEmail')}</p>, { scroll: true, static: true });
    } else {
      const res: any = await sendTestEmail(email, mailer);
      if (res.success) {
        notices.success(<p>{t('emailSent')}</p>, { scroll: true });
      } else {
        notices.error(res.error.map((message) => <p key={message}>{message}</p>), { scroll: true });
      }
    }
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
          onClick={sendEmail}
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

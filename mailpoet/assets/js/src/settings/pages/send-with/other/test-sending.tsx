import { useState } from 'react';
import ReactStringReplace from 'react-string-replace';

import { Tooltip } from 'help-tooltip';
import { onChange, t } from 'common/functions';
import { Button } from 'common/button/button';
import { Input } from 'common/form/input/input';
import { Inputs, Label } from 'settings/components';
import { useAction, useSelector, useSetting } from 'settings/store/hooks';
import { Loading } from 'common/loading';
import { Notice } from 'notices/notice';
import { TestEmailState } from 'settings/store/types';

interface TestSendingWindow extends Window {
  mailpoet_current_user_email: string;
}

declare let window: TestSendingWindow;

export function TestSending() {
  const [email, setEmail] = useState<string>(
    window.mailpoet_current_user_email,
  );
  const [mailer] = useSetting('mta');
  const { state, error } = useSelector('getTestEmailState')();
  const sendTestEmail = useAction('sendTestEmail');

  return (
    <>
      {state === TestEmailState.SENDING && <Loading />}
      {state === TestEmailState.SUCCESS && (
        <Notice type="success" scroll>
          <p>{t('emailSent')}</p>
        </Notice>
      )}
      {state === TestEmailState.FAILURE && (
        <Notice type="error" scroll>
          <p>
            {error.map((message) => (
              <p key={message}>{message}</p>
            ))}
          </p>
        </Notice>
      )}
      <Label title={t('testSending')} htmlFor="mailpoet_mta_test_email" />
      <Inputs>
        <Input
          dimension="small"
          type="text"
          id="mailpoet_mta_test_email"
          value={email}
          onChange={onChange(setEmail)}
        />
        <Button
          type="button"
          dimension="small"
          variant="secondary"
          onClick={() => sendTestEmail(email, mailer)}
        >
          {t('sendTestEmail')}
        </Button>
        <Tooltip
          tooltipId="tooltip-settings-test"
          className="mailpoet_tooltip_icon"
          tooltip={
            <span style={{ pointerEvents: 'all' }}>
              {ReactStringReplace(
                t('testEmailTooltip'),
                /\[link\](.*?)\[\/link\]/g,
                (match, i) => (
                  <a
                    className="mailpoet-link"
                    key={i}
                    target="_blank"
                    rel="noopener noreferrer"
                    href="https://kb.mailpoet.com/article/146-my-newsletters-are-not-being-received"
                  >
                    {match}
                  </a>
                ),
              )}
            </span>
          }
        />
      </Inputs>
    </>
  );
}

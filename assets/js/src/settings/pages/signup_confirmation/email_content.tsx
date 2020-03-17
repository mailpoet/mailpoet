import React from 'react';
import { t, onChange } from 'common/functions';
import { Label, Inputs } from 'settings/components';
import { useSetting } from 'settings/store/hooks';

export default function EmailContent() {
  const [enabled] = useSetting('signup_confirmation', 'enabled');
  const [body, setBody] = useSetting('signup_confirmation', 'body');

  if (!enabled) return null;
  return (
    <>
      <Label
        title={t('emailContent')}
        description={t('emailContentDescription').split('<br />').map((line) => (
          <>
            {line}
            <br />
          </>
        ))}
        htmlFor="signup_confirmation-body"
      />
      <Inputs>
        <textarea
          id="signup_confirmation-body"
          cols={50}
          rows={15}
          data-automation-id="signup_confirmation_email_body"
          value={body}
          onChange={onChange(setBody)}
        />
      </Inputs>
    </>
  );
}

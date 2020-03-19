import React from 'react';
import { Label, Inputs } from 'settings/components';
import { isEmail, t, onChange } from 'common/functions';
import { useSetting, useSelector, useAction } from 'settings/store/hooks';
import SenderEmailAddressWarning from 'common/sender_email_address_warning.jsx';

export default function DefaultSender() {
  const isMssActive = useSelector('isMssActive')();
  const [senderName, setSenderName] = useSetting('sender', 'name');
  const [senderEmail, setSenderEmail] = useSetting('sender', 'address');
  const [replyToName, setReplyToName] = useSetting('reply_to', 'name');
  const [replyToEmail, setReplyToEmail] = useSetting('reply_to', 'address');
  const setErrorFlag = useAction('setErrorFlag');
  const invalidSenderEmail = (senderEmail && !isEmail(senderEmail));
  const invalidReplyToEmail = replyToEmail && !isEmail(replyToEmail);
  React.useEffect(() => {
    setErrorFlag(invalidSenderEmail || invalidReplyToEmail);
  }, [invalidReplyToEmail, invalidSenderEmail, setErrorFlag]);
  return (
    <>
      <Label
        title={t`defaultSenderTitle`}
        description={t`defaultSenderDescription`}
        htmlFor="sender-name"
      />
      <Inputs>
        <label htmlFor="sender-name">{t`from`}</label>
        <input
          type="text"
          id="sender-name"
          placeholder={t`yourName`}
          data-automation-id="from-name-field"
          value={senderName}
          onChange={onChange(setSenderName)}
        />
        <input
          type="text"
          placeholder="from@mydomain.com"
          data-automation-id="from-email-field"
          value={senderEmail}
          onChange={onChange(setSenderEmail)}
        />
        {invalidSenderEmail && (
          <span className="mailpoet_error_item mailpoet_error">
            {t`invalidEmail`}
          </span>
        )}
        <div className="regular-text">
          <SenderEmailAddressWarning
            emailAddress={senderEmail}
            mssActive={isMssActive}
          />
        </div>
        <label htmlFor="reply_to-name">Reply-to</label>
        <input
          type="text"
          id="reply_to-name"
          placeholder={t`yourName`}
          data-automation-id="reply_to-name-field"
          value={replyToName}
          onChange={onChange(setReplyToName)}
        />
        <input
          type="text"
          placeholder="reply_to@mydomain.com"
          data-automation-id="reply_to-email-field"
          value={replyToEmail}
          onChange={onChange(setReplyToEmail)}
        />
        {invalidReplyToEmail && (
          <span className="mailpoet_error_item mailpoet_error">
            {t`invalidEmail`}
          </span>
        )}
      </Inputs>
    </>
  );
}

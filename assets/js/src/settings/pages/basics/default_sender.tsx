import React from 'react';
import MailPoet from 'mailpoet';
import { Label, Inputs } from 'settings/components';
import { useSetting, useSelector } from 'settings/store/hooks';
import SenderEmailAddressWarning from 'common/sender_email_address_warning.jsx';

export default function DefaultSender() {
  const isMssActive = useSelector('isMssActive')();
  const [senderName, setSenderName] = useSetting('sender', 'name');
  const [senderEmail, setSenderEmail] = useSetting('sender', 'address');
  const [replyToName, setReplyToName] = useSetting('reply_to', 'name');
  const [replyToEmail, setReplyToEmail] = useSetting('reply_to', 'address');
  return (
    <>
      <Label
        title={MailPoet.I18n.t('defaultSenderTitle')}
        description={MailPoet.I18n.t('defaultSenderDescription')}
        htmlFor="sender-name"
      />
      <Inputs>
        <label htmlFor="sender-name">{MailPoet.I18n.t('from')}</label>
        <input
          type="text"
          id="sender-name"
          placeholder={MailPoet.I18n.t('yourName')}
          data-automation-id="settings-page-from-name-field"
          value={senderName}
          onChange={(event) => setSenderName(event.target.value)}
        />
        <input
          type="text"
          placeholder="from@mydomain.com"
          data-automation-id="settings-page-from-email-field"
          value={senderEmail}
          onChange={(event) => setSenderEmail(event.target.value)}
        />
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
          placeholder={MailPoet.I18n.t('yourName')}
          data-automation-id="settings-page-from-name-field"
          value={replyToName}
          onChange={(event) => setReplyToName(event.target.value)}
        />
        <input
          type="text"
          placeholder="reply_to@mydomain.com"
          data-automation-id="settings-page-from-email-field"
          value={replyToEmail}
          onChange={(event) => setReplyToEmail(event.target.value)}
        />
      </Inputs>
    </>
  );
}

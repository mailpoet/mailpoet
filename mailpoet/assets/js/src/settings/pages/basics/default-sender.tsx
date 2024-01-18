import { useEffect, useState } from 'react';
import { MailPoet } from 'mailpoet';
import { Label, Inputs } from 'settings/components';
import {
  isEmail,
  t,
  onChange,
  setLowercaseValue,
  extractEmailDomain,
} from 'common/functions';
import { Input } from 'common/form/input/input';
import { useSetting, useSelector, useAction } from 'settings/store/hooks';
import { SenderEmailAddressWarning } from 'common/sender-email-address-warning';

export function DefaultSender({ showModal }) {
  const isMssActive = useSelector('isMssActive')();
  const [senderName, setSenderName] = useSetting('sender', 'name');
  const [senderEmail, setSenderEmail] = useSetting('sender', 'address');
  const [isAuthorized, setIsAuthorized] = useState(true);
  const senderDomain = extractEmailDomain(senderEmail);
  const [showSenderDomainWarning, setShowSenderDomainWarning] = useState(
    !window.mailpoet_verified_sender_domains.includes(senderDomain),
  );
  const [isPartiallyVerifiedDomain, setIsPartiallyVerifiedDomain] = useState(
    window.mailpoet_partially_verified_sender_domains.includes(senderDomain),
  );
  const [replyToName, setReplyToName] = useSetting('reply_to', 'name');
  const [replyToEmail, setReplyToEmail] = useSetting('reply_to', 'address');
  const setErrorFlag = useAction('setErrorFlag');
  const invalidSenderEmail = senderEmail && !isEmail(senderEmail);
  const invalidReplyToEmail = replyToEmail && !isEmail(replyToEmail);

  const isAuthorizedEmail = (email: string) => {
    setIsAuthorized(window.mailpoet_authorized_emails.includes(email));
  };

  const performActionOnBlur = (email: string) => {
    if (!isMssActive) {
      return;
    }

    const emailDomain = extractEmailDomain(email);

    if (window.mailpoet_verified_sender_domains.includes(emailDomain)) {
      // allow user send with any email address from verified domains
      return;
    }

    isAuthorizedEmail(email);

    setShowSenderDomainWarning(true);
    setIsPartiallyVerifiedDomain(
      window.mailpoet_partially_verified_sender_domains.includes(emailDomain),
    );
  };

  const updateSenderEmailController = (email: string) => {
    // Reset email related states
    setIsAuthorized(true);
    setShowSenderDomainWarning(false);
    setIsPartiallyVerifiedDomain(false);
    setSenderEmail(email);
  };

  useEffect(() => {
    setErrorFlag(
      invalidSenderEmail ||
        invalidReplyToEmail ||
        (!isAuthorized && isMssActive),
    );
  }, [
    invalidReplyToEmail,
    invalidSenderEmail,
    setErrorFlag,
    isAuthorized,
    isMssActive,
  ]);
  return (
    <>
      <Label
        title={t('defaultSenderTitle')}
        description={t('defaultSenderDescription')}
        htmlFor="sender-name"
      />
      <Inputs>
        <label htmlFor="sender-name">{t('from')}</label>
        <br />
        <Input
          dimension="small"
          type="text"
          id="sender-name"
          placeholder={t('yourName')}
          data-automation-id="from-name-field"
          value={senderName}
          onChange={onChange(setSenderName)}
        />
        <br />
        <Input
          dimension="small"
          type="text"
          placeholder="from@mydomain.com"
          data-automation-id="from-email-field"
          value={senderEmail}
          onChange={onChange(setLowercaseValue(updateSenderEmailController))}
          onBlur={onChange(performActionOnBlur)}
        />
        <br />
        {invalidSenderEmail && (
          <span className="mailpoet_error_item mailpoet_error">
            {t('invalidEmail')}
          </span>
        )}
        <div className="regular-text">
          <SenderEmailAddressWarning
            emailAddress={senderEmail}
            mssActive={isMssActive}
            showModal={showModal}
            isEmailAuthorized={isAuthorized}
            showSenderDomainWarning={showSenderDomainWarning}
            isPartiallyVerifiedDomain={isPartiallyVerifiedDomain}
            onSuccessfulEmailOrDomainAuthorization={(data) => {
              if (data.type === 'email') {
                setIsAuthorized(true);
                MailPoet.trackEvent('MSS in plugin authorize email', {
                  'authorized email source': 'settings',
                  wasSuccessful: 'yes',
                });
              }
              if (data.type === 'domain') {
                setShowSenderDomainWarning(false);
                setIsPartiallyVerifiedDomain(false);
                MailPoet.trackEvent('MSS in plugin verify sender domain', {
                  'verify sender domain source': 'settings',
                  wasSuccessful: 'yes',
                });
              }
            }}
          />
        </div>
        <label className="mailpoet-settings-inputs-row" htmlFor="reply_to-name">
          Reply-to
        </label>
        <Input
          dimension="small"
          type="text"
          id="reply_to-name"
          placeholder={t('yourName')}
          data-automation-id="reply_to-name-field"
          value={replyToName}
          onChange={onChange(setReplyToName)}
        />
        <br />
        <Input
          dimension="small"
          type="text"
          placeholder="reply_to@mydomain.com"
          data-automation-id="reply_to-email-field"
          value={replyToEmail}
          onChange={onChange(setLowercaseValue(setReplyToEmail))}
        />
        <br />
        {invalidReplyToEmail && (
          <span className="mailpoet_error_item mailpoet_error">
            {t('invalidEmail')}
          </span>
        )}
      </Inputs>
    </>
  );
}

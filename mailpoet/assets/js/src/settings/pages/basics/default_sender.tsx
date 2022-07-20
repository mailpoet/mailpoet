import { useEffect, useState } from 'react';
import { MailPoet } from 'mailpoet';
import { Label, Inputs } from 'settings/components';
import { isEmail, t, onChange, setLowercaseValue } from 'common/functions';
import { Input } from 'common/form/input/input';
import { useSetting, useSelector, useAction } from 'settings/store/hooks';
import { SenderEmailAddressWarning } from 'common/sender_email_address_warning.jsx';

/**
 * @param {string} email - Email address
 * @param {ApiActionType} type - action type
 * @returns {Promise}
 */
const makeApiRequest = (domain: string) =>
  MailPoet.Ajax.post({
    api_version: MailPoet.apiVersion,
    endpoint: 'settings',
    action: 'checkDomainDmarcPolicy',
    data: { domain },
  });

export function DefaultSender() {
  const isMssActive = useSelector('isMssActive')();
  const [senderName, setSenderName] = useSetting('sender', 'name');
  const [senderEmail, setSenderEmail] = useSetting('sender', 'address');
  const [isAuthorized, setIsAuthorized] = useState(true);
  const [showSenderDomainWarning, setShowSenderDomainWarning] = useState(false);
  const [replyToName, setReplyToName] = useSetting('reply_to', 'name');
  const [replyToEmail, setReplyToEmail] = useSetting('reply_to', 'address');
  const setErrorFlag = useAction('setErrorFlag');
  const invalidSenderEmail = senderEmail && !isEmail(senderEmail);
  const invalidReplyToEmail = replyToEmail && !isEmail(replyToEmail);

  const isAuthorizedEmail = (email: string) => {
    if (!isMssActive) {
      return;
    }
    setIsAuthorized(window.mailpoet_authorized_emails.includes(email));
  };

  const checkSenderEmailDomain = (email: string) => {
    const emailAddressDomain = email.split('@').pop().toLowerCase();

    const isDomainVerified =
      window.mailpoet_verified_sender_domains.includes(emailAddressDomain);
    if (isDomainVerified) {
      // do nothing if the email domain is verified
      return;
    }

    // check domain DMARC policy
    makeApiRequest(emailAddressDomain)
      .then((res) => {
        const isDmarcPolicyRetricted = Boolean(
          res?.data?.isDmarcPolicyRetricted,
        );
        setShowSenderDomainWarning(isDmarcPolicyRetricted);
      })
      .catch(() => {
        // do nothing for now when the request fails
      });
  };

  const performActionOnBlur = (data: string) => {
    isAuthorizedEmail(data);

    checkSenderEmailDomain(data);
  };

  const updateSenderEmailController = (email: string) => {
    setIsAuthorized(true);
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
            isEmailAuthorized={isAuthorized}
            showSenderDomainWarning={showSenderDomainWarning}
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

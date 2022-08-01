import { useState } from 'react';
import PropTypes from 'prop-types';
import { noop } from 'lodash';
import { MailPoet } from 'mailpoet';
import ReactStringReplace from 'react-string-replace';
import { AuthorizeSenderEmailAndDomainModal } from 'common/authorize_sender_email_and_domain_modal';

const userHostDomain = window.location.hostname.replace('www.', '');
const suggestedEmailAddress = `contact@${userHostDomain}`;

function SenderEmailAddressWarning({
  emailAddress,
  mssActive,
  isEmailAuthorized,
  showSenderDomainWarning,
  onSuccessfulEmailOrDomainAuthorization,
}) {
  const [showAuthorizedEmailModal, setShowAuthorizedEmailModal] =
    useState(null);

  const loadModal = (event, tab) => {
    event.preventDefault();
    setShowAuthorizedEmailModal(tab);
  };

  const emailAddressDomain = emailAddress.split('@').pop().toLowerCase();

  const displayElements = [];

  const isFreeDomain =
    MailPoet.freeMailDomains.indexOf(emailAddressDomain) > -1;

  if (mssActive) {
    if (!isEmailAuthorized) {
      displayElements.push(
        <div key="authorizeMyEmail">
          <p className="sender_email_address_warning">
            {ReactStringReplace(
              MailPoet.I18n.t('youNeedToAuthorizeTheEmail'),
              '[email]',
              () => emailAddress,
            )}{' '}
            <a
              className="mailpoet-link"
              href="#"
              target="_blank"
              rel="noopener noreferrer"
              onClick={(e) => loadModal(e, 'sender_email')}
            >
              {MailPoet.I18n.t('authorizeMyEmail')}
            </a>
          </p>
        </div>,
      );
    }
    if (showSenderDomainWarning) {
      displayElements.push(
        <div key="authorizeSenderDomain">
          <p className="sender_email_address_warning">
            {ReactStringReplace(
              MailPoet.I18n.t('authorizeSenderDomain'),
              /\[link](.*?)\[\/link]/g,
              (match) => (
                <a
                  key={match}
                  className="mailpoet-link"
                  href="https://kb.mailpoet.com/article/295-spf-and-dkim"
                  target="_blank"
                  rel="noopener noreferrer"
                  onClick={(e) => loadModal(e, 'sender_domain')}
                >
                  {match}
                </a>
              ),
            )}
          </p>
        </div>,
      );
    }
    if (displayElements.length) {
      displayElements.push(
        <div key="AuthorizeSenderEmailAndDomainModal">
          {showAuthorizedEmailModal && (
            <AuthorizeSenderEmailAndDomainModal
              senderEmail={emailAddress}
              onRequestClose={() => {
                setShowAuthorizedEmailModal(null);
              }}
              onSuccessAction={onSuccessfulEmailOrDomainAuthorization}
              showSenderEmailTab={!isEmailAuthorized}
              showSenderDomainTab={showSenderDomainWarning}
              initialTab={showAuthorizedEmailModal}
            />
          )}
        </div>,
      );
    }
    if (displayElements.length)
      return (
        <>
          {' '}
          {displayElements.map((child) => (
            <div key={child.key}>{child}</div>
          ))}
        </>
      );
    return null;
  }
  if (isFreeDomain) {
    return (
      <>
        <p
          className="sender_email_address_warning"
          data-acceptance-id="freemail-sender-warning-old-installation"
        >
          {MailPoet.I18n.t('senderEmailAddressWarning1')}
        </p>
        <p className="sender_email_address_warning">
          {ReactStringReplace(
            MailPoet.I18n.t('senderEmailAddressWarning2'),
            /(%1\$s|%2\$s|<em>.*<\/em>)/,
            (match) => {
              if (match === '%1$s') return suggestedEmailAddress;
              if (match === '%2$s')
                return <em key="sender-email">{emailAddress}</em>;
              return <em key="reply-to">{match.replace(/<\/?em>/g, '')}</em>;
            },
          )}
        </p>
        <p className="sender_email_address_warning">
          <a
            href="https://kb.mailpoet.com/article/259-your-from-address-cannot-be-yahoo-com-gmail-com-outlook-com"
            data-beacon-article="5be5911104286304a71c176e"
            target="_blank"
            rel="noopener noreferrer"
          >
            {MailPoet.I18n.t('senderEmailAddressWarning3')}
          </a>
        </p>
      </>
    );
  }
  return null;
}

SenderEmailAddressWarning.propTypes = {
  emailAddress: PropTypes.string.isRequired,
  mssActive: PropTypes.bool.isRequired,
  isEmailAuthorized: PropTypes.bool,
  showSenderDomainWarning: PropTypes.bool,
  onSuccessfulEmailOrDomainAuthorization: PropTypes.func,
};

SenderEmailAddressWarning.defaultProps = {
  isEmailAuthorized: true, // don't show error message by default
  showSenderDomainWarning: false,
  onSuccessfulEmailOrDomainAuthorization: noop,
};

export { SenderEmailAddressWarning };

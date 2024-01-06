import { useState } from 'react';
import { __, _x } from '@wordpress/i18n';
import { extractEmailDomain } from 'common/functions';
import { MailPoet } from 'mailpoet';
import ReactStringReplace from 'react-string-replace';
import { AuthorizeSenderEmailAndDomainModal } from 'common/authorize-sender-email-and-domain-modal';
import { SenderDomainInlineNotice } from 'common/sender-domain-notice';

const userHostDomain = window.location.hostname.replace('www.', '');
const suggestedEmailAddress = `contact@${userHostDomain}`;

type Props = {
  emailAddress: string;
  mssActive: boolean;
  isEmailAuthorized?: boolean;
  showSenderDomainWarning?: boolean;
  isPartiallyVerifiedDomain?: boolean;
  onSuccessfulEmailOrDomainAuthorization?: (data) => void;
};

function SenderEmailAddressWarning({
  emailAddress,
  mssActive,
  isEmailAuthorized = true,
  showSenderDomainWarning = false,
  isPartiallyVerifiedDomain = false,
  onSuccessfulEmailOrDomainAuthorization = () => {},
}: Props) {
  const [showAuthorizedEmailModal, setShowAuthorizedEmailModal] =
    useState(null);

  const loadModal = (event, tab) => {
    event.preventDefault();
    setShowAuthorizedEmailModal(tab);
  };

  const switchToNewTab = (newTab) => {
    setShowAuthorizedEmailModal(newTab);
  };

  const emailAddressDomain = extractEmailDomain(emailAddress);

  const displayElements = [];

  const isFreeDomain =
    MailPoet.freeMailDomains.indexOf(emailAddressDomain) > -1;

  if (mssActive) {
    if (!isEmailAuthorized) {
      displayElements.push(
        <div key="authorizeMyEmail">
          <p className="sender_email_address_warning">
            {ReactStringReplace(
              __(
                'Not an authorized sender email address. [link]Authorize it now.[/link]',
                'mailpoet',
              ),
              /\[link\](.*?)\[\/link\]/g,
              (match) => (
                <a
                  className="mailpoet-link"
                  href="#"
                  target="_blank"
                  rel="noopener noreferrer"
                  onClick={(e) => loadModal(e, 'sender_email')}
                  key={emailAddress}
                >
                  {match}
                </a>
              ),
            )}
          </p>
        </div>,
      );
    }
    if (showSenderDomainWarning && isEmailAuthorized) {
      displayElements.push(
        <SenderDomainInlineNotice
          emailAddress={emailAddress}
          emailAddressDomain={emailAddressDomain}
          isFreeDomain={isFreeDomain}
          isPartiallyVerifiedDomain={isPartiallyVerifiedDomain}
          authorizeAction={(e) => loadModal(e, 'sender_domain')}
          subscribersCount={window.mailpoet_subscribers_count}
        />,
      );
    }

    displayElements.push(
      <div key="AuthorizeSenderEmailAndDomainModal">
        {showAuthorizedEmailModal && (
          <AuthorizeSenderEmailAndDomainModal
            senderEmail={emailAddress}
            onRequestClose={() => {
              setShowAuthorizedEmailModal(null);
            }}
            showSenderEmailTab={!isEmailAuthorized}
            showSenderDomainTab={showSenderDomainWarning && isEmailAuthorized}
            initialTab={showAuthorizedEmailModal}
            onSuccessAction={onSuccessfulEmailOrDomainAuthorization}
            autoSwitchTab={switchToNewTab}
          />
        )}
      </div>,
    );

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
          {_x(
            'You might not reach the inbox of your subscribers if you use this email address.',
            'In the last step, before sending a newsletter. URL: ?page=mailpoet-newsletters#/send/2',
            'mailpoet',
          )}
        </p>
        <p className="sender_email_address_warning">
          {ReactStringReplace(
            _x(
              'Use an address like %1$s for the Sender and put %2$s in the <em>Reply-to</em> field below.',
              'In the last step, before sending a newsletter. URL: ?page=mailpoet-newsletters#/send/2',
              'mailpoet',
            ),
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
            target="_blank"
            rel="noopener noreferrer"
          >
            {__('Read more', 'mailpoet')}
          </a>
        </p>
      </>
    );
  }
  return null;
}

SenderEmailAddressWarning.displayName = 'SenderEmailAddressWarning';
export { SenderEmailAddressWarning };

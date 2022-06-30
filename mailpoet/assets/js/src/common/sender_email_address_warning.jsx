import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';
import ReactStringReplace from 'react-string-replace';

const userHostDomain = window.location.hostname.replace('www.', '');
const suggestedEmailAddress = `contact@${userHostDomain}`;

function SenderEmailAddressWarning({
  emailAddress,
  mssActive,
  isEmailAuthorized,
}) {
  if (mssActive) {
    if (!isEmailAuthorized) {
      return (
        <span className="mailpoet_error_item mailpoet_error">
          {ReactStringReplace(
            MailPoet.I18n.t('youNeedToAuthorizeTheEmail'),
            '[email]',
            () => emailAddress,
          )}{' '}
          <a
            className="mailpoet-link"
            href={`https://account.mailpoet.com/authorization?email=${emailAddress}`}
            target="_blank"
            rel="noopener noreferrer"
          >
            {MailPoet.I18n.t('authorizeMyEmail')}
          </a>
        </span>
      );
    }
    return null;
  }
  const emailAddressDomain = emailAddress.split('@').pop().toLowerCase();
  if (window.mailpoet_free_domains.indexOf(emailAddressDomain) > -1) {
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
};

SenderEmailAddressWarning.defaultProps = {
  isEmailAuthorized: true, // don't show error message by default
};

export { SenderEmailAddressWarning };

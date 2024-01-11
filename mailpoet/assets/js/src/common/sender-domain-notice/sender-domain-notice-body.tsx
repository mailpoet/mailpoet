import { createInterpolateElement } from '@wordpress/element';
import { escapeHTML } from '@wordpress/escape-html';
import { __ } from '@wordpress/i18n';

function SenderDomainNoticeBody({
  emailAddressDomain,
  isFreeDomain,
  isPartiallyVerifiedDomain,
  isSmallSender,
  onlyShowWarnings = false,
}: {
  emailAddressDomain: string;
  isFreeDomain: boolean;
  isPartiallyVerifiedDomain: boolean;
  isSmallSender: boolean;
  onlyShowWarnings?: boolean;
}) {
  const renderMessage = (messageKey: string) => {
    const messages: { [key: string]: string } = {
      freeSmall:
        "Shared 3rd-party domains like <emailDomain/> will send from MailPoet's shared domain. We recommend that you use your site's branded domain instead.",
      free: "MailPoet cannot send email campaigns from shared 3rd-party domains like <emailDomain/>. Please send from your site's branded domain instead.",
      // TODO: Remove freeWarning after the enforcement date has passed
      freeWarning:
        "Starting on February 1st, 2024, MailPoet will no longer be able to send from email addresses on shared 3rd party domains like <emailDomain/>. Please send from your site's branded domain instead.",
      partiallyVerified:
        'Update your domain settings to improve email deliverability and meet new sending requirements.',
      smallSender:
        'Authenticate to send as <emailDomain/> and improve email deliverability.',
      default: 'Authenticate domain to send new emails as <emailDomain/>.',
    };

    const defaultMessage = messages[messageKey] || messages.default;

    return createInterpolateElement(__(defaultMessage, 'mailpoet'), {
      emailDomain: <strong>{escapeHTML(emailAddressDomain)}</strong>,
    });
  };

  // TODO: Remove after the enforcement date has passed
  if (onlyShowWarnings) {
    if (isFreeDomain) {
      return renderMessage(isSmallSender ? 'freeSmall' : 'freeWarning');
    }
    if (isPartiallyVerifiedDomain) {
      return renderMessage('partiallyVerified');
    }
    return renderMessage('smallSender');
  }

  if (isFreeDomain) {
    return renderMessage(isSmallSender ? 'freeSmall' : 'free');
  }

  if (isPartiallyVerifiedDomain) {
    return renderMessage('partiallyVerified');
  }

  return renderMessage(isSmallSender ? 'smallSender' : 'default');
}

export { SenderDomainNoticeBody };

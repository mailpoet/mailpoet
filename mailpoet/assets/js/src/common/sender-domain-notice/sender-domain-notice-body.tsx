import { createInterpolateElement } from '@wordpress/element';
import { escapeHTML } from '@wordpress/escape-html';
import { __ } from '@wordpress/i18n';

function SenderDomainNoticeBody({
  emailAddressDomain,
  isFreeDomain,
  isPartiallyVerifiedDomain,
  isSmallSender,
  alwaysRewrite = false,
}: {
  emailAddressDomain: string;
  isFreeDomain: boolean;
  isPartiallyVerifiedDomain: boolean;
  isSmallSender: boolean;
  alwaysRewrite?: boolean;
}) {
  const renderMessage = (messageKey: string) => {
    const messages: { [key: string]: string } = {
      freeSmall: __(
        "Shared 3rd-party domains like <emailDomain/> will send from MailPoet's shared domain. We recommend that you use your site's branded domain instead.",
        'mailpoet',
      ),
      free: __(
        "MailPoet cannot send email campaigns from shared 3rd-party domains like <emailDomain/>. Please send from your site's branded domain instead.",
        'mailpoet',
      ),
      partiallyVerified: __(
        'Update your domain settings to improve email deliverability and meet new sending requirements.',
        'mailpoet',
      ),
      smallSender: __(
        'Authenticate to send as <emailDomain/> and improve email deliverability.',
        'mailpoet',
      ),
      default: __(
        'Authenticate domain to send new emails as <emailDomain/>.',
        'mailpoet',
      ),
    };

    const defaultMessage = messages[messageKey] || messages.default;

    return createInterpolateElement(defaultMessage, {
      emailDomain: <strong>{escapeHTML(emailAddressDomain)}</strong>,
    });
  };

  if (isFreeDomain) {
    return renderMessage(isSmallSender || alwaysRewrite ? 'freeSmall' : 'free');
  }

  if (isPartiallyVerifiedDomain) {
    return renderMessage('partiallyVerified');
  }

  return renderMessage(
    isSmallSender || alwaysRewrite ? 'smallSender' : 'default',
  );
}

export { SenderDomainNoticeBody };

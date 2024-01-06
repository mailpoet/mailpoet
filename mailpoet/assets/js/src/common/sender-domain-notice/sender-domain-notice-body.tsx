import { createInterpolateElement } from '@wordpress/element';
import { escapeHTML } from '@wordpress/escape-html';
import { __ } from '@wordpress/i18n';

function SenderDomainNoticeBody({
  emailAddressDomain,
  isFreeDomain,
  isPartiallyVerifiedDomain,
  subscribersCount,
}: {
  emailAddressDomain: string;
  isFreeDomain: boolean;
  isPartiallyVerifiedDomain: boolean;
  subscribersCount: number;
}) {
  const LOWER_LIMIT = 500;

  if (isFreeDomain) {
    if (subscribersCount <= LOWER_LIMIT) {
      return createInterpolateElement(
        __(
          "Shared 3rd-party domains like <emailDomain/> will send from MailPoet's shared domain. We recommend you to use your site's branded domain instead.",
          'mailpoet',
        ),
        {
          emailDomain: <strong>{escapeHTML(emailAddressDomain)}</strong>,
        },
      );
    }

    return createInterpolateElement(
      __(
        "MailPoet cannot send email campaigns from shared 3rd-party domains like <emailDomain/>. Please send from your site's branded domain instead.",
        'mailpoet',
      ),
      {
        emailDomain: <strong>{escapeHTML(emailAddressDomain)}</strong>,
      },
    );
  }

  if (isPartiallyVerifiedDomain) {
    return (
      <>
        {__(
          'Update your domain settings to improve email deliverability and meet new sending requirements.',
          'mailpoet',
        )}
      </>
    );
  }

  // Branded domain not authenticated
  if (subscribersCount <= LOWER_LIMIT) {
    return createInterpolateElement(
      __(
        'Authenticate to send as <emailDomain/> and improve email deliverability.',
        'mailpoet',
      ),
      {
        emailDomain: <strong>{escapeHTML(emailAddressDomain)}</strong>,
      },
    );
  }

  return createInterpolateElement(
    __('Authenticate domain to send new emails as <emailDomain/>.', 'mailpoet'),
    {
      emailDomain: <strong>{escapeHTML(emailAddressDomain)}</strong>,
    },
  );
}

export { SenderDomainNoticeBody };

import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

function SenderActions({
  showAuthorizeButton,
  authorizeAction,
  isFreeDomain,
  isPartiallyVerifiedDomain,
}) {
  const FREE_MAIL_KB_URL =
    'https://kb.mailpoet.com/article/259-your-from-address-cannot-be-yahoo-com-gmail-com-outlook-com';
  const SPF_DKIM_DMARC_KB_URL =
    'https://kb.mailpoet.com/article/295-spf-dkim-dmarc';
  const authorizeButtonLabel = isPartiallyVerifiedDomain
    ? __('Update settings', 'mailpoet')
    : __('Authenticate', 'mailpoet');

  const readMoreLink = isFreeDomain ? FREE_MAIL_KB_URL : SPF_DKIM_DMARC_KB_URL;
  return (
    <>
      {showAuthorizeButton && (
        <Button variant="secondary" target="_blank" onClick={authorizeAction}>
          {authorizeButtonLabel}
        </Button>
      )}
      <Button
        variant="link"
        target="_blank"
        href={readMoreLink}
        rel="noopener noreferrer"
      >
        {__('Learn more', 'mailpoet')}
      </Button>
    </>
  );
}

export { SenderActions };

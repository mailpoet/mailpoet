import jQuery from 'jquery';
import { MailPoet } from 'mailpoet';
import { extractEmailDomain } from 'common/functions';

const loadAuthorizedEmailAddresses = async () => {
  if (MailPoet.mtaMethod !== 'MailPoet') {
    return [];
  }
  const response = await MailPoet.Ajax.post({
    api_version: MailPoet.apiVersion,
    endpoint: 'mailer',
    action: 'getAuthorizedEmailAddresses',
  });
  return response.data || [];
};

const loadVerifiedSenderDomains = async () => {
  if (MailPoet.mtaMethod !== 'MailPoet') {
    return [];
  }
  const response = await MailPoet.Ajax.post({
    api_version: MailPoet.apiVersion,
    endpoint: 'mailer',
    action: 'getVerifiedSenderDomains',
  });
  return response.data || [];
};

const isValidFromAddress = async (fromAddress: string | null) => {
  if (MailPoet.mtaMethod !== 'MailPoet') {
    return true;
  }
  const verifiedDomains = await loadVerifiedSenderDomains();
  const senderDomain = extractEmailDomain(fromAddress);
  if (verifiedDomains.indexOf(senderDomain) !== -1) {
    // allow user send with any email address from verified domain
    return true;
  }
  const addresses = await loadAuthorizedEmailAddresses();
  return addresses.indexOf(fromAddress) !== -1;
};

const resumeMailerSending = () => {
  void MailPoet.Ajax.post({
    api_version: MailPoet.apiVersion,
    endpoint: 'mailer',
    action: 'resumeSending',
  })
    .done(() => {
      MailPoet.Notice.success(MailPoet.I18n.t('mailerSendingResumedNotice'));
    })
    .fail((response: ErrorResponse) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map((error) => error.message),
          { scroll: true },
        );
      }
    });
};

const resumeSendingIfAuthorized = (fromAddress: string | null) =>
  isValidFromAddress(fromAddress).then((valid) => {
    if (!valid) {
      MailPoet.Notice.error(
        MailPoet.I18n.t('mailerSendingNotResumedUnauthorized'),
        { scroll: true },
      );
      MailPoet.trackEvent('Unauthorized email used', {
        'Unauthorized email source': 'send',
      });
      return false;
    }
    return resumeMailerSending();
  });

// use jQuery since some of the targeted notices are added to the DOM using the old
// jQuery-based notice implementation which doesn't trigger pure-JS added listeners
jQuery(($) => {
  $(document).on(
    'click',
    '.notice .mailpoet-js-button-resume-sending',
    (e): void => {
      void resumeSendingIfAuthorized(e.target.value as string);
    },
  );
});

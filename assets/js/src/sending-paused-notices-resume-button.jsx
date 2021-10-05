import jQuery from 'jquery';
import MailPoet from 'mailpoet';

const loadAuthorizedEmailAddresses = async () => {
  if (window.mailpoet_mta_method !== 'MailPoet') {
    return [];
  }
  const response = await MailPoet.Ajax.post({
    api_version: window.mailpoet_api_version,
    endpoint: 'mailer',
    action: 'getAuthorizedEmailAddresses',
  });
  return response.data || [];
};

const isValidFromAddress = async (fromAddress) => {
  if (window.mailpoet_mta_method !== 'MailPoet') {
    return true;
  }
  const addresses = await loadAuthorizedEmailAddresses();
  return addresses.indexOf(fromAddress) !== -1;
};

const resumeMailerSending = () => {
  MailPoet.Ajax.post({
    api_version: window.mailpoet_api_version,
    endpoint: 'mailer',
    action: 'resumeSending',
  }).done(() => {
    MailPoet.Notice.success(MailPoet.I18n.t('mailerSendingResumedNotice'));
    window.mailpoet_listing.forceUpdate();
  }).fail((response) => {
    if (response.errors.length > 0) {
      MailPoet.Notice.error(
        response.errors.map((error) => error.message),
        { scroll: true }
      );
    }
  });
};

const resumeSendingIfAuthorized = (fromAddress) => isValidFromAddress(fromAddress)
  .then((valid) => {
    if (!valid) {
      MailPoet.Notice.error(
        MailPoet.I18n.t('mailerSendingNotResumedUnauthorized'),
        { scroll: true }
      );
      return false;
    }
    return resumeMailerSending();
  });

// use jQuery since some of the targeted notices are added to the DOM using the old
// jQuery-based notice implementation which doesn't trigger pure-JS added listeners
jQuery(($) => {
  $(document).on('click', '.notice .mailpoet-js-button-resume-sending', (e) => (
    resumeSendingIfAuthorized(e.target.value)
  ));
});

import MailPoet from 'mailpoet';
import _ from 'underscore';

const getErrorMessage = (senderAddress) => `<h3>${MailPoet.I18n.t('spfCheckTitle')}</h3>
  <p>${MailPoet.I18n.t('spfCheckMsgWhy').replace('%s', _.escape(senderAddress))}</p>
  <p>${MailPoet.I18n.t('spfCheckMsgEdit').replace('%s', '<em>include:spf.sendingservice.net</em>')}</p>
  <p><a class="button button-primary" href="https://kb.mailpoet.com/article/151-email-authentication-spf-and-dkim" target="_blank">${MailPoet.I18n.t('spfCheckReadMore')}</a></p>`;

const checkSPFRecord = () => MailPoet.Ajax.post({
  api_version: window.mailpoet_api_version,
  endpoint: 'services',
  action: 'checkSPFRecord',
  data: {},
}).fail((response) => {
  if (response.meta.sender_address) {
    MailPoet.Notice.error(
      getErrorMessage(response.meta.sender_address),
      { static: true, scroll: true, id: 'spf_check_error' }
    );
  }
});

export default checkSPFRecord;

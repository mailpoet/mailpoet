import React from 'react';
import ReactDOMServer from 'react-dom/server';
import ReactStringReplace from 'react-string-replace';
import jQuery from 'jquery';
import MailPoet from 'mailpoet';

const mailPoetApiVersion = (window as any).mailpoet_api_version as string;

const handleSave = async (address: string|null) => MailPoet.Ajax.post({
  api_version: mailPoetApiVersion,
  endpoint: 'settings',
  action: 'setAuthorizedFromAddress',
  data: {
    address,
  },
});

const getErrorMessage = (error: any|null): string => {
  if (!error) {
    return MailPoet.I18n.t('setFromAddressEmailUnknownError');
  }

  if (error.error === 'unauthorized') {
    return MailPoet.I18n.t('setFromAddressEmailNotAuthorized').replace(
      /\[link\](.*?)\[\/link\]/g,
      '<a href="https://account.mailpoet.com/authorization" target="_blank" rel="noopener noreferrer">$1</a>'
    );
  }

  return error.message || MailPoet.I18n.t('setFromAddressEmailUnknownError');
};

const getSuccessMessage = (): string => MailPoet.I18n.t('setFromAddressEmailSuccess').replace(
  /\[link\](.*?)\[\/link\]/g,
  '<a href="?page=mailpoet-settings#basics" rel="noopener noreferrer">$1</a>'
);

const showSetFromAddressModal = async () => {
  MailPoet.Modal.popup({
    title: MailPoet.I18n.t('setFromAddressModalTitle'),
    template: ReactDOMServer.renderToString(
      <div id="set-from-address-modal">
        <p>
          {
            ReactStringReplace(
              MailPoet.I18n.t('setFromAddressModalDescription'),
              /\[link\](.*?)\[\/link\]/g,
              (match) => (
                <a
                  key="setFromAddressModalDescriptionLink"
                  href="https://account.mailpoet.com/authorization"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  {match}
                </a>
              )
            )
          }
        </p>

        <input
          id="mailpoet_set_from_address_modal_address"
          type="text"
          placeholder="from@mydomain.com"
          data-parsley-required
          data-parsley-type="email"
        />

        <input
          id="mailpoet_set_from_address_modal_save"
          className="button button-primary"
          type="submit"
          value={MailPoet.I18n.t('setFromAddressModalSave')}
        />
      </div>
    ),
    onInit: () => {
      const saveButton = document.getElementById('mailpoet_set_from_address_modal_save') as HTMLInputElement;
      const addressInput = document.getElementById('mailpoet_set_from_address_modal_address') as HTMLInputElement;
      const addressValidator = jQuery(addressInput).parsley();

      saveButton.addEventListener('click', async () => {
        addressValidator.validate();
        if (!addressValidator.isValid()) {
          return;
        }

        const address = addressInput.value.trim() || null;
        if (!address) {
          return;
        }
        try {
          await handleSave(address);
          MailPoet.Modal.close();

          // remove unauthorized email notices
          const unauthorizedEmailNotice = document.querySelector('[data-notice="unauthorized-email-addresses-notice"]');
          if (unauthorizedEmailNotice) {
            unauthorizedEmailNotice.remove();
          }
          const unauthorizedEmailInNewsletterNotice = document.querySelector('[data-notice="unauthorized-email-in-newsletters-addresses-notice"]');
          if (unauthorizedEmailInNewsletterNotice) {
            unauthorizedEmailInNewsletterNotice.remove();
          }
          MailPoet.Notice.success(getSuccessMessage());
        } catch (e) {
          const error = e.errors && e.errors[0] ? e.errors[0] : null;
          const message = getErrorMessage(error);
          addressValidator.addError('saveError', { message });
        }
      });

      addressInput.addEventListener('input', () => {
        addressValidator.removeError('saveError');
      });
    },
  });
};

export default showSetFromAddressModal;

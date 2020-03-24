import React from 'react';
import ReactDOMServer from 'react-dom/server';
import ReactStringReplace from 'react-string-replace';
import MailPoet from 'mailpoet';

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
        />

        <input
          id="mailpoet_set_from_address_modal_save"
          className="button button-primary"
          type="submit"
          value={MailPoet.I18n.t('setFromAddressModalSave')}
        />
      </div>
    ),
  });
};

export default showSetFromAddressModal;

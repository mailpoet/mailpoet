import React, { useState } from 'react';
import ReactStringReplace from 'react-string-replace';
import jQuery from 'jquery';
import MailPoet from 'mailpoet';
import Modal from 'common/modal/modal.jsx';
import { GlobalContext } from 'context';

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

const getSuccessMessage = (): JSX.Element => (
  <p>
    { ReactStringReplace(
      MailPoet.I18n.t('setFromAddressEmailSuccess'),
      /\[link\](.*?)\[\/link\]/g,
      (match) => (
        <a
          key="setFromAddressModalBasicsTabLink"
          href="?page=mailpoet-settings#basics"
          rel="noopener noreferrer"
        >
          {match}
        </a>
      )
    )}
  </p>
);

const removeUnauthorizedEmailNotices = () => {
  const unauthorizedEmailNotice = document.querySelector('[data-notice="unauthorized-email-addresses-notice"]');
  if (unauthorizedEmailNotice) {
    unauthorizedEmailNotice.remove();
  }
  const unauthorizedEmailInNewsletterNotice = document.querySelector('[data-notice="unauthorized-email-in-newsletters-addresses-notice"]');
  if (unauthorizedEmailInNewsletterNotice) {
    unauthorizedEmailInNewsletterNotice.remove();
  }
};

type Props = {
  onRequestClose: () => void,
};

const SetFromAddressModal = ({ onRequestClose }: Props) => {
  const [address, setAddress] = useState(null);
  const { notices } = React.useContext<any>(GlobalContext);

  return (
    <Modal
      title={MailPoet.I18n.t('setFromAddressModalTitle')}
      onRequestClose={onRequestClose}
    >
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
          onChange={(event) => {
            setAddress(event.target.value.trim() || null);
            const addressValidator = jQuery('#mailpoet_set_from_address_modal_address').parsley();
            addressValidator.removeError('saveError');
          }}
        />

        <input
          id="mailpoet_set_from_address_modal_save"
          className="button button-primary"
          type="submit"
          value={MailPoet.I18n.t('setFromAddressModalSave')}
          onClick={async () => {
            const addressValidator = jQuery('#mailpoet_set_from_address_modal_address').parsley();
            addressValidator.validate();
            if (!addressValidator.isValid()) {
              return;
            }
            if (!address) {
              return;
            }
            try {
              await handleSave(address);
              onRequestClose();
              removeUnauthorizedEmailNotices();
              notices.success(getSuccessMessage(), { timeout: false });
            } catch (e) {
              const error = e.errors && e.errors[0] ? e.errors[0] : null;
              const message = getErrorMessage(error);
              addressValidator.addError('saveError', { message });
            }
          }}
        />
      </div>
    </Modal>
  );
};

export default SetFromAddressModal;

import { useContext, useState } from 'react';
import ReactStringReplace from 'react-string-replace';
import jQuery from 'jquery';
import { MailPoet } from 'mailpoet';
import { Modal } from 'common/modal/modal';
import { GlobalContext } from 'context';
import { noop } from 'lodash';
import { ErrorResponse, isErrorResponse } from '../ajax';
import { AuthorizeSenderEmailModal } from './authorize_sender_email_modal';
import { Button } from './button/button';

/**
 * @param {string|null} address
 * @returns {Promise}
 */
const handleSave = (address: string | null) =>
  MailPoet.Ajax.post({
    api_version: MailPoet.apiVersion,
    endpoint: 'settings',
    action: 'setAuthorizedFromAddress',
    data: {
      address,
    },
  });

const getErrorMessage = (
  error: ErrorResponse['errors'][number] | null,
): string => {
  if (!error) {
    return MailPoet.I18n.t('setFromAddressEmailUnknownError');
  }

  if (error.error === 'unauthorized') {
    return '';
  }

  return error.message || MailPoet.I18n.t('setFromAddressEmailUnknownError');
};

const getSuccessMessage = (): JSX.Element => (
  <p>
    {ReactStringReplace(
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
      ),
    )}
  </p>
);

const removeUnauthorizedEmailNotices = () => {
  const unauthorizedEmailNotice = document.querySelector(
    '[data-notice="unauthorized-email-addresses-notice"]',
  );
  if (unauthorizedEmailNotice) {
    unauthorizedEmailNotice.remove();
  }
  const unauthorizedEmailInNewsletterNotice = document.querySelector(
    '[data-notice="unauthorized-email-in-newsletters-addresses-notice"]',
  );
  if (unauthorizedEmailInNewsletterNotice) {
    unauthorizedEmailInNewsletterNotice.remove();
  }
  const unauthorizedEmailInNewsletterDynamicNotice = document.querySelector(
    '[data-id="mailpoet_authorization_error"]',
  );
  if (unauthorizedEmailInNewsletterDynamicNotice) {
    unauthorizedEmailInNewsletterDynamicNotice.remove();
  }
};

type Props = {
  onRequestClose: () => void;
  setAuthorizedAddress?: (address: string) => void;
};

function SetFromAddressModal({ onRequestClose, setAuthorizedAddress }: Props) {
  const [address, setAddress] = useState<string>();
  const [showAuthorizedEmailModal, setShowAuthorizedEmailModal] =
    useState(false);
  const [showAuthorizedEmailErrorMessage, setShowAuthorizedEmailErrorMessage] =
    useState(false);
  const [loading, setLoading] = useState(false);
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const { notices } = useContext<any>(GlobalContext);

  return (
    <Modal
      title={MailPoet.I18n.t('setFromAddressModalTitle')}
      onRequestClose={onRequestClose}
      contentClassName="set-from-address-modal"
    >
      {showAuthorizedEmailModal && (
        <AuthorizeSenderEmailModal
          useModal
          senderEmail={address}
          onRequestClose={() => {
            setShowAuthorizedEmailModal(false);
          }}
          setAuthorizedAddress={(emailAddres) => {
            setAuthorizedAddress(emailAddres);
            setShowAuthorizedEmailErrorMessage(false);
            onRequestClose();
            notices.success(getSuccessMessage(), { timeout: false });
            MailPoet.trackEvent('MSS in plugin authorize email', {
              'authorized email source': 'SetFromAddressModal',
              wasSuccessful: 'yes',
            });
          }}
        />
      )}

      <p>
        {ReactStringReplace(
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
          ),
        )}
      </p>

      <input
        id="mailpoet-set-from-address-modal-input"
        type="text"
        placeholder="from@mydomain.com"
        data-parsley-required
        data-parsley-type="email"
        onChange={(event) => {
          setAddress(event.target.value.trim() || null);
          const addressValidator = jQuery(
            '#mailpoet-set-from-address-modal-input',
          ).parsley();
          addressValidator.removeError('saveError');
        }}
      />
      <p className="sender_email_address_warning">
        {showAuthorizedEmailErrorMessage &&
          ReactStringReplace(
            MailPoet.I18n.t('setFromAddressEmailNotAuthorized'),
            /\[link\](.*?)\[\/link\]/g,
            (string, index) => (
              <a
                key={index}
                href={`https://account.mailpoet.com/authorization?email=${encodeURIComponent(
                  address,
                )}`}
                onClick={(event) => {
                  event.preventDefault();
                  setShowAuthorizedEmailModal(true);
                }}
                target="_blank"
                rel="noopener noreferrer"
              >
                {string}
              </a>
            ),
          )}
      </p>

      <Button
        withSpinner={loading}
        type="submit"
        onClick={async () => {
          const addressValidator = jQuery(
            '#mailpoet-set-from-address-modal-input',
          ).parsley();
          addressValidator.validate();
          if (!addressValidator.isValid()) {
            return;
          }
          if (!address) {
            return;
          }
          try {
            setLoading(true);
            await handleSave(address);
            setLoading(false);
            setAuthorizedAddress(address);
            onRequestClose();
            removeUnauthorizedEmailNotices();
            notices.success(getSuccessMessage(), { timeout: false });
          } catch (e) {
            setLoading(false);
            const error =
              isErrorResponse(e) && e.errors[0] ? e.errors[0] : null;
            if (error.error === 'unauthorized') {
              MailPoet.trackEvent('Unauthorized email used', {
                'Unauthorized email source': 'modal',
              });
              setShowAuthorizedEmailErrorMessage(true);
            }
            const message = getErrorMessage(error);
            if (message) {
              addressValidator.addError('saveError', { message });
            }
          }
        }}
      >
        {MailPoet.I18n.t('setFromAddressModalSave')}
      </Button>
    </Modal>
  );
}

SetFromAddressModal.defaultProps = {
  setAuthorizedAddress: noop,
};

export { SetFromAddressModal };

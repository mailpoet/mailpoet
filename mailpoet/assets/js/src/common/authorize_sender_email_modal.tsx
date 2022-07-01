import { useEffect, useState } from 'react';
import ReactStringReplace from 'react-string-replace';
import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';
import { Modal } from 'common/modal/modal';
import { Loader } from 'common';

type ApiActionType = 'create' | 'confirm';

/**
 * @param {string} email - Email address
 * @param {ApiActionType} type - action type
 * @returns {Promise}
 */
const makeApiRequest = (email: string, type: ApiActionType = 'create') =>
  MailPoet.Ajax.post({
    api_version: MailPoet.apiVersion,
    endpoint: 'settings',
    action:
      type === 'create'
        ? 'authorizeSenderEmailAddress'
        : 'confirmSenderEmailAddressIsAuthorized',
    data: {
      email,
    },
  });

type Props = {
  senderEmail: string;
  onRequestClose: () => void;
};

function AuthorizeSenderEmailModal({ senderEmail, onRequestClose }: Props) {
  const [apiResponseType, setApiResponseType] = useState<boolean>(null);

  const senderEmailAddress = String(senderEmail).toLowerCase();

  useEffect(() => {
    if (!senderEmailAddress) {
      return;
    }

    makeApiRequest(senderEmailAddress)
      .then((res) => {
        setApiResponseType(Boolean(res?.data));
      })
      .catch(() => {
        setApiResponseType(false);
      });
  }, [senderEmailAddress]);

  return (
    <Modal
      title={MailPoet.I18n.t('authorizeSenderEmailModalTitle').replace(
        '[senderEmail]',
        senderEmailAddress,
      )}
      onRequestClose={onRequestClose}
      contentClassName="authorize-sender-email-modal"
    >
      {apiResponseType && (
        <p>
          {ReactStringReplace(
            MailPoet.I18n.t('authorizeSenderEmailModalDescription'),
            /\[bold\](.*?)\[\/bold\]/g,
            (match, i) => (
              <strong key={i}>{match}</strong>
            ),
          )}
        </p>
      )}

      {apiResponseType === false && (
        <p>{MailPoet.I18n.t('authorizeSenderEmailMessageError')}</p>
      )}

      <Loader size={64} />
    </Modal>
  );
}

AuthorizeSenderEmailModal.propTypes = {
  senderEmail: PropTypes.string.isRequired,
};

export { AuthorizeSenderEmailModal };

import { useEffect, useRef, useState } from 'react';
import ReactStringReplace from 'react-string-replace';
import PropTypes from 'prop-types';
import { noop } from 'lodash';
import moment from 'moment';
import { MailPoet } from 'mailpoet';
import { Modal } from 'common/modal/modal';
import { Button, Loader } from 'common';
import { isErrorResponse, ErrorResponse } from 'ajax';
import { Grid } from 'common/grid';

const SET_INTERVAL_PERFORM_REQUEST_EVERY_SECONDS = 15;

const SET_INTERVAL_STOP_POLLING_AFTER_HOURS = 2; // hours

type ApiActionType = 'create' | 'confirm' | 'setup';

/**
 * @param {string} email - Email address
 * @param {ApiActionType} type - action type
 * @returns {Promise}
 */
const makeApiRequest = (email: string, type: ApiActionType = 'create') => {
  let requestAction = 'authorizeSenderEmailAddress';
  let requestData: unknown = { email };

  if (type === 'confirm') {
    requestAction = 'confirmSenderEmailAddressIsAuthorized';
  } else if (type === 'setup') {
    requestAction = 'setAuthorizedFromAddress';
    requestData = {
      address: email,
    };
  }

  return MailPoet.Ajax.post({
    api_version: MailPoet.apiVersion,
    endpoint: 'settings',
    action: requestAction,
    data: requestData,
  });
};

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

const clearCurrentInterval = (intervalID: NodeJS.Timeout) => {
  clearInterval(intervalID);
};

type Props = {
  senderEmail: string;
  onRequestClose: () => void;
  setAuthorizedAddress?: (emailAddress: string) => void;
  useModal: boolean;
};

function AuthorizeSenderEmailModal({
  senderEmail,
  onRequestClose,
  setAuthorizedAddress,
  useModal,
}: Props) {
  const [createEmailApiResponse, setCreateEmailApiResponse] =
    useState<boolean>(null);
  const [createEmailApiError, setCreateEmailApiError] = useState('');
  const [confirmEmailApiResponse, setConfirmEmailApiResponse] =
    useState<boolean>(null);
  const [showLoader, setShowLoader] = useState<boolean>(true);
  const setIntervalId = useRef<NodeJS.Timeout>();
  const setIntervalStopTime = useRef<number>();
  const modalIsOpened = useRef<boolean>(false);

  const senderEmailAddress = String(senderEmail).toLowerCase();

  const executeAction = () => {
    if (!modalIsOpened.current) return; // do nothing if modal is not opened

    const currentIntervalId = setIntervalId.current;
    const currentIntervalStopTime = setIntervalStopTime.current;

    if (currentIntervalStopTime && Date.now() >= currentIntervalStopTime) {
      // stop polling the server after X number of hours
      clearCurrentInterval(currentIntervalId);
      return;
    }

    makeApiRequest(senderEmailAddress, 'confirm')
      .then((res) => {
        const response = Boolean(res?.data?.isAuthorized);

        if (response) {
          clearCurrentInterval(currentIntervalId);
          return makeApiRequest(senderEmailAddress, 'setup');
        }
        throw new Error('Error: unconfirmed');
      })
      .then(() => {
        setCreateEmailApiResponse(null);
        setShowLoader(false);
        setConfirmEmailApiResponse(true);
        setAuthorizedAddress(senderEmailAddress);
        removeUnauthorizedEmailNotices();
      })
      .catch(() => {
        // do nothing if unconfirmed
      });
  };

  useEffect(() => {
    if (!senderEmailAddress) {
      return null;
    }

    modalIsOpened.current = true;

    clearCurrentInterval(setIntervalId.current);

    makeApiRequest(senderEmailAddress)
      .then((res) => {
        if (!modalIsOpened.current) return; // do nothing if modal is not opened

        const response = Boolean(res?.data?.status);
        setCreateEmailApiResponse(response);
        setShowLoader(response);
        if (response) {
          // if pending or already authorized perform the check ahead
          executeAction();

          // start polling on success response
          setIntervalStopTime.current = moment()
            .add(SET_INTERVAL_STOP_POLLING_AFTER_HOURS, 'hours')
            .valueOf();

          setIntervalId.current = setInterval(
            executeAction,
            1000 * SET_INTERVAL_PERFORM_REQUEST_EVERY_SECONDS,
          );
        }
      })
      .catch((e: ErrorResponse) => {
        if (!modalIsOpened.current) return; // do nothing if modal is not opened

        const error =
          isErrorResponse(e) && e.errors[0] && e.errors[0].message
            ? e.errors[0].message
            : '';
        setCreateEmailApiError(error);

        setCreateEmailApiResponse(false);
        setShowLoader(false);
      });

    return () => {
      modalIsOpened.current = false;
      clearCurrentInterval(setIntervalId.current);
    };
    /**
     * I'm using eslint-disable-line react-hooks/exhaustive-deps here to fix eslint warning.
     *
     * The suggestion unfortunately will not apply in this case.
     * setAuthorizedAddress is a props callback used to notify the parent component the sender email has been authorized
     * Adding it to the useEffect dependency array just causes an unnecessary double render
     * We can't make use of useCallback here
     * MAILPOET-4300
     */
  }, [senderEmailAddress]); // eslint-disable-line react-hooks/exhaustive-deps

  const content = (
    <>
      {createEmailApiResponse && (
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
      {createEmailApiResponse === false && (
        <>
          <strong> {createEmailApiError} </strong>
          <p>{MailPoet.I18n.t('authorizeSenderEmailMessageError')}</p>
        </>
      )}

      {showLoader && (
        <Grid.Column align="center">
          <Loader size={64} />
        </Grid.Column>
      )}

      {confirmEmailApiResponse && (
        <>
          <p>{MailPoet.I18n.t('authorizeSenderEmailMessageSuccess')}</p>
          <Button onClick={onRequestClose} className="button-on-top">
            {' '}
            {MailPoet.I18n.t('close')}{' '}
          </Button>
        </>
      )}
    </>
  );

  return useModal ? (
    <Modal
      title={MailPoet.I18n.t('authorizeSenderEmailModalTitle').replace(
        '[senderEmail]',
        senderEmailAddress,
      )}
      onRequestClose={onRequestClose}
      contentClassName="authorize-sender-email-modal"
    >
      {content}
    </Modal>
  ) : (
    <div>{content}</div>
  );
}

AuthorizeSenderEmailModal.propTypes = {
  senderEmail: PropTypes.string.isRequired,
  useModal: PropTypes.bool,
};

AuthorizeSenderEmailModal.defaultProps = {
  setAuthorizedAddress: noop,
  useModal: true,
};

export { AuthorizeSenderEmailModal };

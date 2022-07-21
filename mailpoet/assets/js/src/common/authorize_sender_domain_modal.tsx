import { useEffect, useRef, useState } from 'react';
import PropTypes from 'prop-types';
import { noop } from 'lodash';
import { MailPoet } from 'mailpoet';
import { Modal } from 'common/modal/modal';
import {
  ManageSenderDomain,
  SenderDomainDnsItem,
  SenderDomainEntity,
} from 'common/manage_sender_domain';
import { isErrorResponse, Response, ErrorResponse } from 'ajax';

interface SenderDomainApiResponseType extends Response {
  data: SenderDomainDnsItem[];
}

type VerifyResponseType = {
  dns: SenderDomainDnsItem[];
  ok: boolean;
  error?: string;
};
interface SenderDomainApiVerifyResponseType extends Response {
  data: VerifyResponseType;
}

type ApiActionType = 'fetch' | 'create' | 'verify';

/**
 * @param {string} domain - Sender Domain
 * @param {ApiActionType} type - action type
 * @returns {Promise}
 */
const makeApiRequest = (domain: string, type: ApiActionType = 'fetch') => {
  let requestAction = 'getAuthorizedSenderDomains';

  if (type === 'create') {
    requestAction = 'createAuthorizedSenderDomain';
  } else if (type === 'verify') {
    requestAction = 'verifyAuthorizedSenderDomain';
  }

  return MailPoet.Ajax.post({
    api_version: MailPoet.apiVersion,
    endpoint: 'settings',
    action: requestAction,
    data: { domain },
  });
};

const getApiErrorMessage = (error: { error?: ErrorResponse }): string =>
  isErrorResponse(error) && error.errors[0] && error.errors[0].message
    ? error.errors[0].message
    : '';

const generateRowData = (senderDomain: string, dns: SenderDomainDnsItem[]) => {
  const row: SenderDomainEntity[] = [
    {
      domain: senderDomain,
      dns,
    },
  ];
  return row;
};

type Props = {
  senderDomain: string;
  onRequestClose: () => void;
  setVerifiedSenderDomain?: (senderDomain: string) => void;
};

function AuthorizeSenderDomainModal({
  senderDomain,
  onRequestClose,
  setVerifiedSenderDomain,
}: Props): JSX.Element {
  const [errorMessage, setErrorMessage] = useState('');
  const [loadingButton, setLoadingButton] = useState(false);
  const [rowData, setRowData] = useState<SenderDomainEntity[]>([]);
  const modalIsOpened = useRef<boolean>(false);

  const performStateUpdate = (callback: (param) => void, args) => {
    if (!modalIsOpened.current) return; // do nothing if modal is not opened
    callback(args);
  };

  const verifyDnsButtonClicked = async () => {
    setLoadingButton(true);

    try {
      const res: SenderDomainApiVerifyResponseType = await makeApiRequest(
        senderDomain,
        'verify',
      );
      if (!modalIsOpened.current) return;

      setRowData(generateRowData(senderDomain, res.data.dns));
      if (res.data.ok) {
        // record verified, close the modal
        setErrorMessage('');
        setVerifiedSenderDomain(senderDomain);
        onRequestClose();
      }
    } catch (e) {
      const error: { error?: ErrorResponse; meta?: VerifyResponseType } = e;
      if (!modalIsOpened.current) return;

      setRowData(generateRowData(senderDomain, error?.meta?.dns || []));
      const apiErrorMessage = getApiErrorMessage(e);
      setErrorMessage(apiErrorMessage || error?.meta?.error || '');
    }

    performStateUpdate(setLoadingButton, false);
  };

  useEffect(() => {
    if (!senderDomain) {
      return null;
    }
    modalIsOpened.current = true;

    const allSenderDomains = window.mailpoet_all_sender_domains || [];

    (async () => {
      try {
        if (allSenderDomains.includes(senderDomain)) {
          // sender domain already exist
          const res: SenderDomainApiResponseType = await makeApiRequest(
            senderDomain,
          );
          performStateUpdate(
            setRowData,
            generateRowData(senderDomain, res.data),
          );
        } else {
          // create new sender domain
          const res: SenderDomainApiResponseType = await makeApiRequest(
            senderDomain,
            'create',
          );
          performStateUpdate(
            setRowData,
            generateRowData(senderDomain, res.data),
          );
        }
      } catch (e) {
        const apiErrorMessage = getApiErrorMessage(e);

        performStateUpdate(setErrorMessage, apiErrorMessage);
      }
    })().catch(() => {
      // do nothing
    });

    return () => {
      modalIsOpened.current = false;
    };
  }, [senderDomain]);

  return (
    <Modal
      onRequestClose={onRequestClose}
      contentClassName="authorize-sender-domain-modal"
    >
      {errorMessage && (
        <strong className="mailpoet_error_item mailpoet_error">
          {' '}
          {errorMessage}{' '}
        </strong>
      )}
      <ManageSenderDomain
        rows={rowData}
        verifyDnsButtonClicked={verifyDnsButtonClicked}
        loadingButton={loadingButton}
      />
    </Modal>
  );
}

AuthorizeSenderDomainModal.propTypes = {
  senderDomain: PropTypes.string.isRequired,
};

AuthorizeSenderDomainModal.defaultProps = {
  setVerifiedSenderDomain: noop,
};

export { AuthorizeSenderDomainModal };

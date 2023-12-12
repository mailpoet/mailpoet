import { __ } from '@wordpress/i18n';
import { Button } from 'common/index';
import { Messages } from 'common/premium-key/messages';
import { MssStatus } from 'settings/store/types';
import { MailPoet } from 'mailpoet';
import { select } from '@wordpress/data';
import { STORE_NAME } from 'settings/store/store-name';
import { useContext, useState } from 'react';
import { GlobalContext } from 'context';
import { useAction, useSelector, useSetting } from 'settings/store/hooks';

type KeyState = {
  is_approved: boolean;
};

type KeyActivationButtonPropType = {
  label: string;
  isFullWidth?: boolean;
};

export function KeyActivationButton({
  label,
  isFullWidth = false,
}: KeyActivationButtonPropType) {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const { notices } = useContext<any>(GlobalContext);
  const state = useSelector('getKeyActivationState')();
  const setState = useAction('updateKeyActivationState');
  const verifyMssKey = useAction('verifyMssKey');
  const verifyPremiumKey = useAction('verifyPremiumKey');
  const sendCongratulatoryMssEmail = useAction('sendCongratulatoryMssEmail');
  const [apiKeyState] = useSetting('mta', 'mailpoet_api_key_state', 'data');

  async function activationCallback() {
    await verifyMssKey(state.key);
    void sendCongratulatoryMssEmail();
    void setState({ fromAddressModalCanBeShown: true });
  }

  const showPendingApprovalNotice =
    state.inProgress === false &&
    state.mssStatus === MssStatus.VALID_MSS_ACTIVE &&
    apiKeyState &&
    (apiKeyState as KeyState).is_approved === false;

  const buttonIsDisabled = state.key === '' || state.key === null;
  const [showRefreshMessage, setShowRefreshMessage] = useState(true);

  const verifyKey = async () => {
    if (!state.key) {
      notices.error(
        <p>
          {__('Please specify a license key before validating it.', 'mailpoet')}
        </p>,
        { scroll: true },
      );
      return;
    }
    await setState({
      mssStatus: null,
      premiumStatus: null,
      premiumInstallationStatus: null,
    });
    MailPoet.Modal.loading(true);
    void setState({ inProgress: true });
    await verifyMssKey(state.key);
    const currentMssStatus =
      select(STORE_NAME).getKeyActivationState().mssStatus;
    if (currentMssStatus === MssStatus.VALID_MSS_ACTIVE) {
      await sendCongratulatoryMssEmail();
    }
    await verifyPremiumKey(state.key);
    void setState({ inProgress: false });
    MailPoet.Modal.loading(false);
    void setState({ fromAddressModalCanBeShown: true });
    // pending approval refresh link should only show on refresh of the page and should get hidden after the refresh button is clicked
    setShowRefreshMessage(false);
  };

  return (
    <>
      <Button
        className="mailpoet-verify-key-button"
        type="button"
        onClick={verifyKey}
        isFullWidth={isFullWidth}
        isDisabled={buttonIsDisabled}
      >
        {label}
      </Button>
      {state.isKeyValid !== null &&
        Messages(
          state,
          showPendingApprovalNotice,
          activationCallback,
          verifyKey,
          showRefreshMessage,
        )}
    </>
  );
}

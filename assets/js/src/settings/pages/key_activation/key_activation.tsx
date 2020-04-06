import React, { useContext } from 'react';
import MailPoet from 'mailpoet';
import { useSelector, useAction, useSetting } from 'settings/store/hooks';
import { GlobalContext } from 'context';
import { t } from 'common/functions';
import { MssStatus } from 'settings/store/types';
import { Label, Inputs } from 'settings/components';
import SetFromAddressModal from 'common/set_from_address_modal';
import { KeyMessages, MssMessages, PremiumMessages } from './messages';

export default function KeyActivation() {
  const { notices } = useContext<any>(GlobalContext);
  const state = useSelector('getKeyActivationState')();
  const setState = useAction('updateKeyActivationState');
  const verifyMssKey = useAction('verifyMssKey');
  const verifyPremiumKey = useAction('verifyPremiumKey');
  const installPremiumPlugin = useAction('installPremiumPlugin');
  const activatePremiumPlugin = useAction('activatePremiumPlugin');
  const [senderAddress, setSenderAddress] = useSetting('sender', 'address');
  const [unauthorizedAddresses, setUnauthorizedAddresses] = useSetting('authorized_emails_addresses_check');

  const setAuthorizedAddress = async (address: string) => {
    await setSenderAddress(address);
    await setUnauthorizedAddresses(null);
  };

  const verifyKey = async (event) => {
    if (!state.key) {
      notices.error(<p>{t('premiumTabNoKeyNotice')}</p>, { scroll: true });
      return;
    }
    const isUserTriggered = event.isTrusted;
    await setState({
      mssStatus: null,
      premiumStatus: null,
      premiumInstallationStatus: null,
    });
    MailPoet.Modal.loading(true);
    const mssStatus: MssStatus = (await verifyMssKey(state.key, isUserTriggered)) as any;
    await verifyPremiumKey(state.key);
    MailPoet.Modal.loading(false);
    if (isUserTriggered) {
      const authorizedAddressNeeded = !senderAddress || unauthorizedAddresses;
      if (mssStatus === MssStatus.VALID_MSS_ACTIVE && authorizedAddressNeeded) {
        setState({ showFromAddressModal: true });
      }
    }
  };

  return (
    <div className="mailpoet-settings-grid">
      <Label
        htmlFor="mailpoet_premium_key"
        title={t('premiumTabActivationKeyLabel')}
        description={t('premiumTabDescription')}
      />
      <Inputs>
        <input
          type="text"
          id="mailpoet_premium_key"
          className="regular-text"
          name="premium[premium_key]"
          value={state.key || ''}
          onChange={(event) => setState({
            mssStatus: null,
            premiumStatus: null,
            premiumInstallationStatus: null,
            key: event.target.value.trim() || null,
          })}
        />
        <button
          type="button"
          id="mailpoet_premium_key_verify"
          className="button-secondary"
          onClick={verifyKey}
        >
          {t('premiumTabVerifyButton')}
        </button>
        {state.isKeyValid !== null && (
          <div className="key-activation-messages">
            <KeyMessages />
            {state.mssStatus !== null && (
              <MssMessages
                keyMessage={state.mssMessage}
                activationCallback={() => verifyMssKey(state.key, true)}
              />
            )}
            {state.premiumStatus !== null && (
              <PremiumMessages
                keyMessage={state.premiumMessage}
                installationStatus={state.premiumInstallationStatus}
                installationCallback={installPremiumPlugin}
                activationCallback={() => activatePremiumPlugin(false)}
              />
            )}
          </div>
        )}
      </Inputs>
      {state.showFromAddressModal && (
        <SetFromAddressModal
          onRequestClose={() => setState({ showFromAddressModal: false })}
          setAuthorizedAddress={setAuthorizedAddress}
        />
      )}
    </div>
  );
}

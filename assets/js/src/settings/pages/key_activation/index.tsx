import React, { useContext } from 'react';
import MailPoet from 'mailpoet';
import { useSelector, useAction } from 'settings/store/hooks';
import { GlobalContext } from 'context';
import { t } from 'common/functions';
import { KeyMessages, MssMessages, PremiumMessages } from './messages';

export default function KeyActivation() {
  const { notices } = useContext<any>(GlobalContext);
  const {
    key, isKeyValid, premiumStatus, premiumMessage, mssStatus, mssMessage, premiumInstallationStatus,
  } = useSelector('getKeyActivationState')();
  const setState = useAction('updateKeyActivationState');
  const verifyMssKey = useAction('verifyMssKey');
  const verifyPremiumKey = useAction('verifyPremiumKey');
  const installPremiumPlugin = useAction('installPremiumPlugin');
  const activatePremiumPlugin = useAction('activatePremiumPlugin');

  const verifyKey = async (event) => {
    if (!key) {
      notices.error(<p>{t('premiumTabNoKeyNotice')}</p>, { scroll: true });
      return;
    }
    await setState({
      mssStatus: null,
      premiumStatus: null,
      premiumInstallationStatus: null,
    });
    MailPoet.Modal.loading(true);
    await verifyMssKey(key, event.isTrusted);
    await verifyPremiumKey(key);
    MailPoet.Modal.loading(false);
  };

  return (
    <table className="form-table">
      <tbody>
        <tr>
          <th scope="row">
            <label htmlFor="mailpoet_premium_key">
              {MailPoet.I18n.t('premiumTabActivationKeyLabel')}
            </label>
            <p className="description">
              {MailPoet.I18n.t('premiumTabDescription')}
            </p>
          </th>
          <td>
            <div>
              <input
                type="text"
                id="mailpoet_premium_key"
                className="regular-text"
                name="premium[premium_key]"
                value={key || ''}
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
                {MailPoet.I18n.t('premiumTabVerifyButton')}
              </button>
            </div>
            {isKeyValid !== null && (
              <div className="key-activation-messages">
                <KeyMessages />
                {mssStatus !== null && (
                  <MssMessages
                    keyMessage={mssMessage}
                    activationCallback={() => verifyMssKey(key, true)}
                  />
                )}
                {premiumStatus !== null && (
                  <PremiumMessages
                    keyMessage={premiumMessage}
                    installationStatus={premiumInstallationStatus}
                    installationCallback={installPremiumPlugin}
                    activationCallback={() => activatePremiumPlugin(false)}
                  />
                )}
              </div>
              {keyValid !== null && (
              <div className="key-activation-messages">
                <KeyMessages keyValid={keyValid} />
                {mssStatus !== null && (
                  <MssMessages
                    keyStatus={mssStatus}
                    keyMessage={mssKeyMessage}
                    activationCallback={() => verifyMailPoetSendingServiceKey(true)}
                  />
                )}
                {premiumStatus !== null && (
                  <PremiumMessages
                    keyStatus={premiumStatus}
                    keyMessage={premiumMessage}
                    installationStatus={premiumInstallationStatus}
                    installationCallback={installPremiumPlugin}
                    activationCallback={() => activatePremiumPlugin()}
                  />
                )}
              </div>
            )}
          </td>
        </tr>
      </tbody>
    </table>
      {
    showSetFromAddressModal && (
      <SetFromAddressModal
        onRequestClose={() => setShowSetFromAddressModal(false)}
      />
    )
  }
    </>
  );
}

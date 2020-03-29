import React, { useMemo, useState } from 'react';
import MailPoet from 'mailpoet';
import KeyMessages from 'settings/pages/key_activation/messages/key_messages.jsx';
import { MssMessages } from 'settings/pages/key_activation/messages/mss_messages.jsx';
import { PremiumMessages } from 'settings/pages/key_activation/messages/premium_messages.jsx';
import { PremiumInstallationStatus } from 'settings/pages/key_activation/messages/premium_installation_messages.jsx';
import { useSetting, useSelector } from 'settings/store/hooks';

const getSettings = async () => MailPoet.Ajax.post({
  api_version: window.mailpoet_api_version,
  endpoint: 'settings',
  action: 'get',
});

const requestServicesApi = async (key, action) => MailPoet.Ajax.post({
  api_version: window.mailpoet_api_version,
  endpoint: 'services',
  action,
  data: { key },
});

const requestPremiumApi = async (action) => MailPoet.Ajax.post({
  api_version: window.mailpoet_api_version,
  endpoint: 'premium',
  action,
});

const activateMss = async (key) => MailPoet.Ajax.post({
  api_version: window.mailpoet_api_version,
  endpoint: 'settings',
  action: 'set',
  data: {
    mta_group: 'mailpoet',
    mta: {
      method: 'MailPoet',
      mailpoet_api_key: key,
    },
    signup_confirmation: {
      enabled: '1',
    },
  },
});

const PremiumTab = () => {
  const [premiumKey] = useSetting('premium', 'premium_key');
  const [mssKey] = useSetting('mta', 'mailpoet_api_key');
  const [key, setKey] = useState(premiumKey || mssKey);
  const getMssStatus = useSelector('getMssStatus');
  const getPremiumStatus = useSelector('getPremiumStatus');
  const [premiumStatus, setPremiumStatus] = useState(key ? getPremiumStatus() : null);
  const [premiumMessage, setPremiumMessage] = useState(null);
  const [premiumInstallationStatus, setPremiumInstallationStatus] = useState(null);
  const [mssStatus, setMssStatus] = useState(key ? getMssStatus() : null);
  const [mssKeyMessage, setMssKeyMessage] = useState(null);
  const [showSetFromAddressModal, setShowSetFromAddressModal] = useState(false);

  // key is considered valid if either Premium or MSS check passes
  const keyValid = useMemo(() => {
    if (premiumStatus !== 'invalid' || mssStatus !== 'invalid') {
      return true;
    }
    return (premiumStatus === null || mssStatus === null) ? null : false;
  }, [premiumStatus, mssStatus]);

  const activatePremiumPlugin = async (isAfterInstall = false) => {
    const status = PremiumInstallationStatus;
    const activateStatus = isAfterInstall ? status.INSTALL_ACTIVATING : status.ACTIVATE_ACTIVATING;
    const doneStatus = isAfterInstall ? status.INSTALL_DONE : status.ACTIVATE_DONE;
    const errorStatus = isAfterInstall ? status.INSTALL_ACTIVATING_ERROR : status.ACTIVATE_ERROR;

    setPremiumInstallationStatus(activateStatus);
    try {
      await requestPremiumApi('activatePlugin');
    } catch (error) {
      setPremiumInstallationStatus(errorStatus);
      return false;
    }
    setPremiumInstallationStatus(doneStatus);
    return true;
  };

  const installPremiumPlugin = async () => {
    setPremiumInstallationStatus(PremiumInstallationStatus.INSTALL_INSTALLING);
    try {
      await requestPremiumApi('installPlugin');
    } catch (error) {
      setPremiumInstallationStatus(PremiumInstallationStatus.INSTALL_INSTALLING_ERROR);
      return false;
    }
    return activatePremiumPlugin(true);
  };

  const verifyMailPoetPremiumKey = async () => {
    try {
      const response = await requestServicesApi(key, 'checkPremiumKey');
      setPremiumMessage(null);

      // install/activate Premium plugin
      let pluginActive = response.meta.premium_plugin_active;
      if (!response.meta.premium_plugin_installed) {
        setPremiumStatus('valid_premium_plugin_being_installed');
        pluginActive = await installPremiumPlugin();
      } else if (!response.meta.premium_plugin_active) {
        setPremiumStatus('valid_premium_plugin_being_activated');
        pluginActive = await activatePremiumPlugin();
      } else {
        setPremiumStatus('valid_premium_plugin_active');
      }

      MailPoet.trackEvent(
        'User has validated a Premium key',
        {
          'MailPoet Free version': MailPoet.version,
          'Premium plugin is active': pluginActive,
        }
      );
    } catch (error) {
      setPremiumStatus('invalid');
      setPremiumMessage(error.errors.map((e) => e.message).join(' ') || null);
      MailPoet.trackEvent(
        'User has failed to validate a Premium key',
        {
          'MailPoet Free version': MailPoet.version,
          'Premium plugin is active': !!MailPoet.premiumVersion,
        }
      );
    }
  };

  async function verifyMailPoetSendingServiceKey(activateMssIfKeyValid) {
    try {
      const response = await requestServicesApi(key, 'checkMSSKey');
      setMssKeyMessage(response.data.message || null);
      if (activateMssIfKeyValid) {
        await activateMss(key);
        setMssStatus('valid_mss_active');
      } else {
        setMssStatus('valid_mss_not_active');
      }
    } catch (error) {
      setMssStatus('invalid');
      setMssKeyMessage(error.errors.map((e) => e.message).join(' ') || null);
    }
    window.updateMSSActivationUI();
  }

  return (
    <>
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
                  onChange={(event) => {
                    setKey(event.target.value.trim() || null);
                    setPremiumStatus(null);
                    setPremiumInstallationStatus(null);
                    setMssStatus(null);
                  }}
                />
                <button
                  type="button"
                  id="mailpoet_premium_key_verify"
                  className="button-secondary"
                  onClick={async (event) => {
                    if (!key) {
                      MailPoet.Notice.error(
                        MailPoet.I18n.t('premiumTabNoKeyNotice'),
                        { scroll: true },
                      );
                      return;
                    }

                    setPremiumStatus(null);
                    setPremiumInstallationStatus(null);
                    setMssStatus(null);

                    MailPoet.Modal.loading(true);
                    const isUserTriggered = event.isTrusted;
                    await verifyMailPoetSendingServiceKey(isUserTriggered);
                    await verifyMailPoetPremiumKey();
                    MailPoet.Modal.loading(false);

                    // show modal to set authorized FROM address, if needed
                    if (isUserTriggered) {
                      const settings = await getSettings();
                      const mssActive = settings.data.mta && settings.data.mta.method === 'MailPoet';
                      const authorizedAddressNeeded = !settings.data.sender.address
                        || settings.data.authorized_emails_addresses_check;

                      if (mssActive && authorizedAddressNeeded) {
                        setShowSetFromAddressModal(true);
                      }
                    }
                  }}
                >
                  {MailPoet.I18n.t('premiumTabVerifyButton')}
                </button>
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
      {showSetFromAddressModal && (
        <SetFromAddressModal
          onRequestClose={() => setShowSetFromAddressModal(false)}
        />
      )}
    </>
  );
};

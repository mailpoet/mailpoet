import PropTypes from 'prop-types';
import React, { useMemo, useState } from 'react';
import ReactDOM from 'react-dom';
import MailPoet from 'mailpoet';
import KeyMessages from 'settings/premium_tab/messages/key_messages.jsx';
import MssMessages from 'settings/premium_tab/messages/mss_messages.jsx';
import { PremiumStatus, PremiumMessages } from 'settings/premium_tab/messages/premium_messages.jsx';
import { PremiumInstallationStatus } from 'settings/premium_tab/messages/premium_installation_messages.jsx';

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

const PremiumTab = (props) => {
  const [key, setKey] = useState(props.activationKey);
  const [premiumStatus, setPremiumStatus] = useState(key ? props.premiumStatus : null);
  const [premiumMessage, setPremiumMessage] = useState(null);
  const [premiumInstallationStatus, setPremiumInstallationStatus] = useState(null);
  const [mssKeyValid, setMssKeyValid] = useState(key ? props.mssKeyValid : null);
  const [mssKeyMessage, setMssKeyMessage] = useState(null);

  // key is considered valid if either Premium or MSS check passes
  const keyValid = useMemo(() => {
    if (premiumStatus > PremiumStatus.KEY_INVALID || mssKeyValid) {
      return true;
    }
    return (premiumStatus === null || mssKeyValid === null) ? null : false;
  }, [premiumStatus, mssKeyValid]);

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
        setPremiumStatus(PremiumStatus.KEY_VALID_PREMIUM_PLUGIN_BEING_INSTALLED);
        pluginActive = await installPremiumPlugin();
      } else if (!response.meta.premium_plugin_active) {
        setPremiumStatus(PremiumStatus.KEY_VALID_PREMIUM_PLUGIN_BEING_ACTIVATED);
        pluginActive = await activatePremiumPlugin();
      } else {
        setPremiumStatus(PremiumStatus.KEY_VALID_PREMIUM_PLUGIN_ACTIVE);
      }

      MailPoet.trackEvent(
        'User has validated a Premium key',
        {
          'MailPoet Free version': window.mailpoet_version,
          'Premium plugin is active': pluginActive,
        }
      );
    } catch (error) {
      setPremiumStatus(PremiumStatus.KEY_INVALID);
      setPremiumMessage(error.errors.map((e) => e.message).join(' ') || null);
      MailPoet.trackEvent(
        'User has failed to validate a Premium key',
        {
          'MailPoet Free version': window.mailpoet_version,
          'Premium plugin is active': props.premiumPluginActive,
        }
      );
    }
  };

  async function verifyMailPoetSendingServiceKey(activateMssIfKeyValid) {
    try {
      const response = await requestServicesApi(key, 'checkMSSKey');
      setMssKeyValid(true);
      setMssKeyMessage(response.data.message || null);

      if (activateMssIfKeyValid) {
        await activateMss(key);
      }
    } catch (error) {
      setMssKeyValid(false);
      setMssKeyMessage(error.errors.map((e) => e.message).join(' ') || null);
    }
    window.updateMSSActivationUI();
  }

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
                onChange={(event) => {
                  setKey(event.target.value.trim() || null);
                  setPremiumStatus(null);
                  setPremiumInstallationStatus(null);
                  setMssKeyValid(null);
                }}
              />
              {' '}
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
                  setMssKeyValid(null);

                  MailPoet.Modal.loading(true);
                  const isUserTriggered = event.isTrusted;
                  await verifyMailPoetSendingServiceKey(isUserTriggered);
                  await verifyMailPoetPremiumKey();
                  MailPoet.Modal.loading(false);
                }}
              >
                {MailPoet.I18n.t('premiumTabVerifyButton')}
              </button>
            </div>
            {keyValid !== null && (
              <div className="key-activation-messages">
                <KeyMessages keyValid={keyValid} />
                {mssKeyValid !== null && (
                  <MssMessages
                    keyValid={mssKeyValid}
                    keyMessage={mssKeyMessage}
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
  );
};

PremiumTab.propTypes = {
  activationKey: PropTypes.string,
  premiumStatus: PropTypes.number.isRequired,
  mssKeyValid: PropTypes.bool.isRequired,
  premiumPluginActive: PropTypes.bool.isRequired,
};

PremiumTab.defaultProps = {
  activationKey: null,
};

const container = document.getElementById('settings-premium-tab');
if (container) {
  const getPremiumStatus = () => {
    const keyValid = window.mailpoet_premium_key_valid;
    const pluginInstalled = window.mailpoet_premium_plugin_installed;
    const pluginActive = !!window.mailpoet_premium_version;
    if (!keyValid) {
      return PremiumStatus.KEY_INVALID;
    }
    if (pluginActive) {
      return PremiumStatus.KEY_VALID_PREMIUM_PLUGIN_ACTIVE;
    }
    return pluginInstalled
      ? PremiumStatus.KEY_VALID_PREMIUM_PLUGIN_NOT_ACTIVE
      : PremiumStatus.KEY_VALID_PREMIUM_PLUGIN_NOT_INSTALLED;
  };

  ReactDOM.render(
    <PremiumTab
      activationKey={window.mailpoet_activation_key}
      premiumStatus={getPremiumStatus()}
      mssKeyValid={window.mailpoet_mss_key_valid}
      premiumPluginActive={!!window.mailpoet_premium_version}
    />,
    container
  );
}

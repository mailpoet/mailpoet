import PropTypes from 'prop-types';
import React, { useMemo, useState } from 'react';
import ReactDOM from 'react-dom';
import MailPoet from 'mailpoet';
import KeyMessages from 'settings/premium_tab/messages/key_messages.jsx';
import MssMessages from 'settings/premium_tab/messages/mss_messages.jsx';
import { PremiumStatus, PremiumMessages } from 'settings/premium_tab/messages/premium_messages.jsx';
import { PremiumInstallationStatus } from 'settings/premium_tab/messages/premium_installation_messages.jsx';

const request = async (url) => {
  try {
    const response = await fetch(url);
    return response.ok;
  } catch (error) {
    return false;
  }
};

const requestServicesApi = async (key, action) => MailPoet.Ajax.post({
  api_version: window.mailpoet_api_version,
  endpoint: 'services',
  action,
  data: { key },
});

const PremiumTab = (props) => {
  const [key, setKey] = useState(props.activationKey);
  const [premiumStatus, setPremiumStatus] = useState(key ? props.premiumStatus : null);
  const [premiumMessage, setPremiumMessage] = useState(null);
  const [premiumInstallationStatus, setPremiumInstallationStatus] = useState(null);
  const [mssKeyValid, setMssKeyValid] = useState(key ? props.mssKeyValid : null);
  const [mssKeyMessage, setMssKeyMessage] = useState(null);

  let premiumActivateUrl = props.premiumActivateUrl;

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
    if (!await request(premiumActivateUrl)) {
      setPremiumInstallationStatus(errorStatus);
      return;
    }
    setPremiumInstallationStatus(doneStatus);
  };

  const installPremiumPlugin = async () => {
    setPremiumInstallationStatus(PremiumInstallationStatus.INSTALL_INSTALLING);
    if (!await request(props.premiumInstallUrl)) {
      setPremiumInstallationStatus(PremiumInstallationStatus.INSTALL_INSTALLING_ERROR);
      return;
    }

    // refetch 'plugin_activate_url' since it's only set after installation
    try {
      const response = await requestServicesApi(key, 'checkPremiumKey');
      premiumActivateUrl = response.meta.premium_activate_url;
    } catch (error) {
      setPremiumInstallationStatus(PremiumInstallationStatus.INSTALL_INSTALLING_ERROR);
      return;
    }
    await activatePremiumPlugin(true);
  };

  const verifyMailPoetPremiumKey = async () => {
    try {
      const response = await requestServicesApi(key, 'checkPremiumKey');
      setPremiumMessage(null);

      // install/activate Premium plugin
      if (!response.meta.premium_plugin_installed) {
        setPremiumStatus(PremiumStatus.KEY_VALID_PREMIUM_PLUGIN_BEING_INSTALLED);
        await installPremiumPlugin();
      } else if (!response.meta.premium_plugin_active) {
        setPremiumStatus(PremiumStatus.KEY_VALID_PREMIUM_PLUGIN_BEING_ACTIVATED);
        await activatePremiumPlugin();
      } else {
        setPremiumStatus(PremiumStatus.KEY_VALID_PREMIUM_PLUGIN_ACTIVE);
      }

      MailPoet.trackEvent(
        'User has validated a Premium key',
        {
          'MailPoet Free version': window.mailpoet_version,
          'Premium plugin is active': response.meta.premium_plugin_active,
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

  async function verifyMailPoetSendingServiceKey() {
    try {
      const response = await requestServicesApi(key, 'checkMSSKey');
      setMssKeyValid(true);
      setMssKeyMessage(response.data.message || null);
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
                onChange={(event) => setKey(event.target.value.trim() || null)}
              />
              {' '}
              <button
                type="button"
                id="mailpoet_premium_key_verify"
                className="button-secondary"
                onClick={async () => {
                  if (!key) {
                    MailPoet.Notice.error(
                      MailPoet.I18n.t('premiumTabNoKeyNotice'),
                      { scroll: true },
                    );
                    return;
                  }

                  MailPoet.Modal.loading(true);
                  await verifyMailPoetSendingServiceKey();
                  await verifyMailPoetPremiumKey();
                  MailPoet.Modal.loading(false);
                }}
              >
                {MailPoet.I18n.t('premiumTabVerifyButton')}
              </button>
            </div>
            {keyValid !== null && (
              <>
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
              </>
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
  premiumInstallUrl: PropTypes.string.isRequired,
  premiumActivateUrl: PropTypes.string,
};

PremiumTab.defaultProps = {
  activationKey: null,
  premiumActivateUrl: null,
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
      premiumInstallUrl={window.mailpoet_premium_install_url}
      premiumActivateUrl={window.mailpoet_premium_activate_url || null}
    />,
    container
  );
}

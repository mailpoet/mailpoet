import PropTypes from 'prop-types';
import React, { useState } from 'react';
import ReactDOM from 'react-dom';
import MailPoet from 'mailpoet';
import MssMessages from 'settings/premium_tab/messages/mss_messages.jsx';
import { PremiumStatus, PremiumMessages } from 'settings/premium_tab/messages/premium_messages.jsx';
import { PremiumInstallationStatus } from 'settings/premium_tab/messages/premium_installation_messages.jsx';

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

  const verifyMailPoetPremiumKey = async () => {
    try {
      const response = await requestServicesApi(key, 'checkPremiumKey');
      setPremiumMessage(null);

      // install/activate Premium plugin
      if (!response.meta.premium_plugin_installed) {
        setPremiumStatus(PremiumStatus.KEY_VALID_PREMIUM_PLUGIN_BEING_INSTALLED);
        //TODO
      } else if (!response.meta.premium_plugin_active) {
        setPremiumStatus(PremiumStatus.KEY_VALID_PREMIUM_PLUGIN_BEING_ACTIVATED);
        //TODO
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
                installationCallback="" //TODO
                activationCallback="" //TODO
              />
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

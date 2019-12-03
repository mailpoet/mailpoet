import PropTypes from 'prop-types';
import React, { useState } from 'react';
import ReactDOM from 'react-dom';
import MailPoet from 'mailpoet';
import PremiumMessages from 'settings/premium_tab/messages/premium_messages.jsx';
import MssMessages from 'settings/premium_tab/messages/mss_messages.jsx';

const requestServicesApi = async (key, action) => MailPoet.Ajax.post({
  api_version: window.mailpoet_api_version,
  endpoint: 'services',
  action,
  data: { key },
});

const PremiumTab = (props) => {
  const [key, setKey] = useState(props.activationKey);
  const [premiumKeyValid, setPremiumKeyValid] = useState(key ? props.premiumKeyValid : null);
  const [mssKeyValid, setMssKeyValid] = useState(key ? props.mssKeyValid : null);
  const [premiumKeyMessage, setPremiumKeyMessage] = useState(null);
  const [mssKeyMessage, setMssKeyMessage] = useState(null);

  const verifyMailPoetPremiumKey = async () => {
    try {
      const response = await requestServicesApi(key, 'checkPremiumKey');
      setPremiumKeyMessage(null);
      MailPoet.trackEvent(
        'User has validated a Premium key',
        {
          'MailPoet Free version': window.mailpoet_version,
          'Premium plugin is active': response.meta.premium_plugin_active,
        }
      );
    } catch (error) {
      setPremiumKeyValid(false);
      setPremiumKeyMessage(error.errors.map((e) => e.message).join(' ') || null);
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
                  await verifyMailPoetPremiumKey();
                  await verifyMailPoetSendingServiceKey();
                  MailPoet.Modal.loading(false);
                }}
              >
                {MailPoet.I18n.t('premiumTabVerifyButton')}
              </button>
            </div>
            {premiumKeyValid !== null && (
              <PremiumMessages
                keyValid={premiumKeyValid}
                keyMessage={premiumKeyMessage}
              />
            )}
            {mssKeyValid !== null && (
              <MssMessages
                keyValid={mssKeyValid}
                keyMessage={mssKeyMessage}
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
  premiumKeyValid: PropTypes.bool.isRequired,
  mssKeyValid: PropTypes.bool.isRequired,
  premiumPluginActive: PropTypes.bool.isRequired,
};

PremiumTab.defaultProps = {
  activationKey: null,
};

const container = document.getElementById('settings-premium-tab');
if (container) {
  ReactDOM.render(
    <PremiumTab
      activationKey={window.mailpoet_activation_key}
      premiumKeyValid={window.mailpoet_premium_key_valid}
      mssKeyValid={window.mailpoet_mss_key_valid}
      premiumPluginActive={!!window.mailpoet_premium_version}
    />,
    container
  );
}

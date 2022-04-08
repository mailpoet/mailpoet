import { Link, useHistory } from 'react-router-dom';
import ReactStringReplace from 'react-string-replace';

import { useSelector, useAction, useSetting } from 'settings/store/hooks';
import { MssStatus, PremiumStatus } from 'settings/store/types';
import { t } from 'common/functions';

interface SendWithChoiceWindow extends Window {
  mailpoet_free_plan_url: string;
}

declare let window: SendWithChoiceWindow;

export default function SendWithChoice() {
  const history = useHistory();
  const isMssActive = useSelector('isMssActive')();
  const [key] = useSetting('mta', 'mailpoet_api_key');
  const { mssStatus, premiumStatus } = useSelector('getKeyActivationState')();
  const isMssKeyValid = mssStatus !== null && mssStatus !== MssStatus.INVALID;
  const isPremiumKeyValid =
    premiumStatus !== null && premiumStatus !== PremiumStatus.INVALID;
  const freePlanUrl = window.mailpoet_free_plan_url;
  const setSetting = useAction('setSetting');
  const saveSettings = useAction('saveSettings');
  const activateMss = async () => {
    await setSetting(['mta_group'], 'mailpoet');
    await setSetting(['mta', 'method'], 'MailPoet');
    await setSetting(['mta', 'mailpoet_api_key'], key);
    await setSetting(['signup_confirmation', 'enabled'], '1');
    return saveSettings();
  };

  const mssChosen = async (e) => {
    e.preventDefault();
    if (!isMssActive && !isMssKeyValid) {
      history.push('/premium');
    }
    if (!isMssActive && isMssKeyValid) {
      await activateMss();
    }
  };

  const otherChosen = (e) => {
    e.preventDefault();
    history.push('/mta/other');
  };

  return (
    <ul className="mailpoet-sending-methods">
      <li>
        <label className="mailpoet-sending-method mailpoet-form-checkbox">
          <input type="radio" checked={isMssActive} onChange={mssChosen} />
          <span className="mailpoet-form-radio-control" />
          <h4 className="mailpoet-h4">{t('mssTitle')}</h4>
          <p>
            {isMssActive ? t('youreSendingWithMss') : t('solveSendingProblems')}
          </p>
          <ul className="mailpoet-sending-method-benefits mailpoet_success">
            <li className="mailpoet_success_item">{t('mssBenefit1')}</li>
            <li className="mailpoet_success_item">{t('mssBenefit2')}</li>
            <li className="mailpoet_success_item">{t('mssBenefit3')}</li>
            <li className="mailpoet_success_item">{t('mssBenefit4')}</li>
            <li className="mailpoet_success_item">{t('mssBenefit5')}</li>
          </ul>
          {!isMssKeyValid && !isPremiumKeyValid && (
            <div className="mailpoet-sending-method-actions">
              <a
                className="mailpoet-button button-primary"
                href={freePlanUrl}
                rel="noopener noreferrer"
                target="_blank"
              >
                {t('freeUpto')}
              </a>
              <span>
                &nbsp;
                {t('or')}
                &nbsp;
              </span>
              {ReactStringReplace(
                t('enterYourKey'),
                /\[link\](.*?)\[\/link\]/g,
                (match, i) => (
                  <Link key={i} to="/premium" className="mailpoet-link">
                    {match}
                  </Link>
                ),
              )}
            </div>
          )}
          {!isMssKeyValid && isPremiumKeyValid && (
            <div className="mailpoet-sending-method-actions">
              <i>{t('invalidKeyForMss')}</i>{' '}
              <a
                className="mailpoet-button button-primary"
                href="https://account.mailpoet.com/"
                rel="noopener noreferrer"
                target="_blank"
              >
                {t('getPlan')}
              </a>
            </div>
          )}
        </label>
      </li>
      <li>
        <label className="mailpoet-sending-method mailpoet-form-checkbox">
          <input type="radio" checked={!isMssActive} onChange={otherChosen} />
          <span className="mailpoet-form-radio-control" />
          <h4 className="mailpoet-h4">{t('otherTitle')}</h4>
          <p>
            {t('sendViaHost')} <strong>{t('notRecommended')}</strong>{' '}
            {t('orViaThirdParty')}
          </p>
          <div className="mailpoet-sending-method-actions">
            <Link to="/mta/other" className="mailpoet-link">
              {t('configure')}
            </Link>
          </div>
        </label>
      </li>
    </ul>
  );
}

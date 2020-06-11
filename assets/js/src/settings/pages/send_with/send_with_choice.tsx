import React from 'react';
import classnames from 'classnames';
import ReactStringReplace from 'react-string-replace';

import { useSelector, useAction, useSetting } from 'settings/store/hooks';
import { MssStatus } from 'settings/store/types';
import { t } from 'common/functions';
import { Link } from 'react-router-dom';

export default function SendWithChoice() {
  const isNewUser = useSelector('isNewUser')();
  const isMssActive = useSelector('isMssActive')();
  const [key] = useSetting('mta', 'mailpoet_api_key');
  const { mssStatus } = useSelector('getKeyActivationState')();
  const isMssKeyValid = mssStatus !== null && mssStatus !== MssStatus.INVALID;
  const freePlanUrl = (window as any).mailpoet_free_plan_url;

  const setSetting = useAction('setSetting');
  const saveSettings = useAction('saveSettings');
  const activateMss = async () => {
    await setSetting(['mta_group'], 'mailpoet');
    await setSetting(['mta', 'method'], 'MailPoet');
    await setSetting(['mta', 'mailpoet_api_key'], key);
    await setSetting(['signup_confirmation', 'enabled'], '1');
    return saveSettings();
  };

  return (
    <ul className="mailpoet_sending_methods">
      <li
        data-group="mailpoet"
        className={classnames({
          mailpoet_active: isMssActive,
          mailpoet_invalid_key: !isMssKeyValid,
        })}
      >
        <div className="mailpoet_sending_method_description">
          <h3>{t('mssTitle')}</h3>
          <div className="mailpoet_description">
            {isMssActive && (
              <span id="mailpoet_sending_method_active_text">
                <strong>{t('youreSendingWithMss')}</strong>
              </span>
            )}
            {!isMssActive
              && (
                <span id="mailpoet_sending_method_inactive_text">
                  <strong>{t('solveSendingProblems')}</strong>
                </span>
              )}
            <ul className="sending-method-benefits mailpoet_success">
              <li className="mailpoet_success_item">{t('mssBenefit1')}</li>
              <li className="mailpoet_success_item">{t('mssBenefit2')}</li>
              <li className="mailpoet_success_item">{t('mssBenefit3')}</li>
              <li className="mailpoet_success_item">{t('mssBenefit4')}</li>
              <li className="mailpoet_success_item">{t('mssBenefit5')}</li>
            </ul>
            <p className="mailpoet_sending_methods_help">
              <a
                target="_blank"
                rel="noopener noreferrer"
                data-beacon-article="5a3d4c260428631938003802"
                href="https://kb.mailpoet.com/article/235-video-guide-sending-options"
                className={classnames('mailpoet_badge mailpoet_badge_video', {
                  mailpoet_badge_video_grey: !isNewUser,
                })}
              >
                <span className="dashicons dashicons-format-video" />
                {t('seeVideo')}
              </a>
            </p>
          </div>
        </div>
        <div className={classnames('mailpoet_status', {
          mailpoet_invalid_key: !isMssActive || !isMssKeyValid,
        })}
        >
          {isMssActive && isMssKeyValid
            && (
              <div className="mailpoet_activated">
                <span>{t('activated')}</span>
              </div>
            )}
          <div className="mailpoet_actions">
            {!isMssKeyValid && (
              <div className="mailpoet_invalid_key">
                <a className="button-primary" href={freePlanUrl} rel="noopener noreferrer" target="_blank">{t('freeUpto')}</a>
                {' '}
                {t('or')}
                {' '}
                {ReactStringReplace(t('enterYourKey'), /\[link\](.*?)\[\/link\]/g,
                  (match, i) => (
                    <Link key={i} to="/premium">{match}</Link>
                  ))}
              </div>
            )}
            {!isMssActive && isMssKeyValid && (
              <div className="mailpoet_valid_key">
                <button
                  type="button"
                  onClick={activateMss}
                  className="mailpoet_sending_service_activate button-primary"
                >
                  {t('activate')}
                </button>
              </div>
            )}
          </div>
        </div>
      </li>
      <li
        data-group="other"
        className={classnames({ mailpoet_active: !isMssActive })}
      >
        <div className="mailpoet_sending_method_description">
          <h3>{t('otherTitle')}</h3>
          <div className="mailpoet_description">
            <strong>{t('otherDescription')}</strong>
            <ul className="sending-method-benefits mailpoet_error">
              <li className="mailpoet_error_item">{t('otherCons1')}</li>
              <li className="mailpoet_error_item">{t('otherCons2')}</li>
              <li className="mailpoet_error_item">{t('otherCons3')}</li>
              <li className="mailpoet_error_item">
                {ReactStringReplace(t('otherCons4'), /\[link\](.*?)\[\/link\]/g,
                  (match, i) => (
                    <a
                      key={i}
                      target="_blank"
                      rel="noopener noreferrer"
                      href="https://wordpress.org/plugins/bounce-handler-mailpoet/"
                    >
                      {match}
                    </a>
                  ))}
              </li>
            </ul>
          </div>
        </div>
        <div className="mailpoet_status">
          <span>{t('activated')}</span>
          <div className="mailpoet_actions">
            {isMssActive && (
              <Link
                className="button-primary mailpoet_other_sending_method_action"
                to="/mta/other"
              >
                {t('activate')}
              </Link>
            )}
            {!isMssActive && (
              <Link
                className="button-secondary mailpoet_other_sending_method_action"
                to="/mta/other"
              >
                {t('configure')}
              </Link>
            )}
          </div>
        </div>
      </li>
    </ul>
  );
}

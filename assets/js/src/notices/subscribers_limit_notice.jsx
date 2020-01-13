import React from 'react';
import MailPoet from 'mailpoet';
import Notice from 'notices/notice.jsx';

const SubscribersLimitNotice = () => {
  if (!window.mailpoet_subscribers_limit_reached) return null;
  const hasValidApiKey = window.mailpoet_has_valid_api_key;
  const title = MailPoet.I18n.t('subscribersLimitNoticeTitle')
    .replace('[subscribersLimit]', window.mailpoet_subscribers_limit);
  const youReachedTheLimit = MailPoet.I18n.t(hasValidApiKey ? 'yourPlanLimit' : 'freeVersionLimit')
    .replace('[subscribersLimit]', window.mailpoet_subscribers_limit);
  const upgradeLink = hasValidApiKey
    ? 'https://account.mailpoet.com/upgrade'
    : `https://account.mailpoet.com/?s=${window.mailpoet_subscribers_count + 1}`;
  const refreshSubscribers = async () => {
    await MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'services',
      action: 'recheckKeys',
    });
    window.location.reload();
  };

  return (
    <Notice type="error" timeout={false} closable={false}>
      <h3>{title}</h3>
      <p>
        {youReachedTheLimit}
        {' '}
        {MailPoet.I18n.t('youNeedToUpgrade')}
      </p>
      <p>
        <a
          target="_blank"
          rel="noopener noreferrer"
          className="button button-primary"
          href={upgradeLink}
        >
          {MailPoet.I18n.t('upgradeNow')}
        </a>
        {hasValidApiKey && (
        <>
          {' '}
          <button
            type="button"
            className="button"
            onClick={refreshSubscribers}
          >
            {MailPoet.I18n.t('refreshMySubscribers')}
          </button>
        </>
        )}
      </p>
    </Notice>
  );
};

export default SubscribersLimitNotice;

import React from 'react';
import ReactStringReplace from 'react-string-replace';
import MailPoet from 'mailpoet';
import Notice from 'notices/notice.tsx';

function SubscribersLimitNotice() {
  if (!MailPoet.subscribersLimitReached) return null;
  const hasValidApiKey = MailPoet.hasValidApiKey;
  const title = MailPoet.I18n.t('subscribersLimitNoticeTitle')
    .replace('[subscribersLimit]', MailPoet.subscribersLimit);
  const youReachedTheLimit = MailPoet.I18n.t(hasValidApiKey ? 'yourPlanLimit' : 'freeVersionLimit')
    .replace('[subscribersLimit]', MailPoet.subscribersLimit);
  const upgradeLink = hasValidApiKey
    ? `https://account.mailpoet.com/orders/upgrade/${MailPoet.pluginPartialKey}`
    : `https://account.mailpoet.com/?s=${MailPoet.subscribersCount + 1}`;
  const refreshSubscribers = async () => {
    await MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'services',
      action: 'recheckKeys',
    });
    window.location.reload();
  };

  const youCanDisableWpSegmentMessage = ReactStringReplace(
    MailPoet.I18n.t('youCanDisableWPUsersList'),
    /\[link](.*?)\[\/link]/g,
    (match) => (
      <a key="goToSegments" href="?page=mailpoet-segments">{match}</a>
    )
  );

  return (
    <Notice type="error" timeout={false} closable={false} renderInPlace>
      <h3>{title}</h3>
      <p>
        {youReachedTheLimit}
        {' '}
        {MailPoet.I18n.t('youNeedToUpgrade')}
        {MailPoet.wpSegmentState === 'active' ? (
          <>
            <br />
            {youCanDisableWpSegmentMessage}
          </>
        ) : null}
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
}

export default SubscribersLimitNotice;

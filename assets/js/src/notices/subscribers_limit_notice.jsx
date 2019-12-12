import React from 'react';
import MailPoet from 'mailpoet';
import Notice from 'notices/notice.jsx';

const SubscribersLimitNotice = () => {
  if (!window.mailpoet_subscribers_limit_reached) return null;
  return (
    <Notice type="error" timeout={false} closable={false}>
      <h3>
        {
          MailPoet.I18n.t('subscribersLimitNoticeTitle')
            .replace('[subscribersLimit]', window.mailpoet_subscribers_limit)
        }
      </h3>
      <p>
        {
          MailPoet.I18n.t('subscribersLimitNoticeContent')
            .replace('[subscribersLimit]', window.mailpoet_subscribers_limit)
        }
      </p>
      <p>
        <a
          target="_blank"
          rel="noopener noreferrer"
          className="button button-primary"
          href={`https://account.mailpoet.com/?s=${window.mailpoet_subscribers_count + 1}`}
        >
          {MailPoet.I18n.t('upgradeNow')}
        </a>
      </p>
    </Notice>
  );
};

export default SubscribersLimitNotice;

import React from 'react';
import Notice from 'notices/notice';
import MailPoet from 'mailpoet';

type Props = {
  mssKeyInvalid: boolean;
  subscribersCount: number;
}

const InvalidMssKeyNotice = ({ mssKeyInvalid, subscribersCount }: Props) => {
  if (!mssKeyInvalid) return null;
  return (
    <Notice type="error" timeout={false} closable={false} renderInPlace>
      <h3>{MailPoet.I18n.t('allSendingPausedHeader')}</h3>
      <p>{MailPoet.I18n.t('allSendingPausedBody')}</p>
      <p>
        <a
          href={`https://account.mailpoet.com?s=${subscribersCount}`}
          className="button button-primary"
          target="_blank"
          rel="noopener noreferrer"
        >
          {MailPoet.I18n.t('allSendingPausedLink')}
        </a>
      </p>
    </Notice>
  );
};

export default InvalidMssKeyNotice;

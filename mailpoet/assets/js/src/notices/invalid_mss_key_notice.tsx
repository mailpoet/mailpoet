import { Notice } from 'notices/notice';
import { MailPoet } from 'mailpoet';
import ReactStringReplace from 'react-string-replace';

type Props = {
  mssKeyInvalid: boolean;
  subscribersCount: number;
};

function InvalidMssKeyNotice({ mssKeyInvalid, subscribersCount }: Props) {
  if (!mssKeyInvalid) return null;
  return (
    <Notice type="error" timeout={false} closable={false} renderInPlace>
      <h3>{MailPoet.I18n.t('allSendingPausedHeader')}</h3>
      <p>
        {ReactStringReplace(
          MailPoet.I18n.t('allSendingPausedBody'),
          /\[link\](.*?)\[\/link\]/g,
          (match) => (
            <a href="?page=mailpoet-settings#premium" key="check-sending">
              {match}
            </a>
          ),
        )}
      </p>
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
}

InvalidMssKeyNotice.displayName = 'InvalidMssKeyNotice';
export { InvalidMssKeyNotice };

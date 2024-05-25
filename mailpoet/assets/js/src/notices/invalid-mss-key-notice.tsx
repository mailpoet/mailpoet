import { Notice } from 'notices/notice';
import { MailPoet } from 'mailpoet';
import ReactStringReplace from 'react-string-replace';

type Props = {
  mssKeyInvalid: boolean;
  pluginPartialKey: string;
  premiumKeyValid: boolean;
  subscribersCount: number;
};

function InvalidMssKeyNotice({
  mssKeyInvalid,
  pluginPartialKey,
  premiumKeyValid,
  subscribersCount,
}: Props) {
  if (!mssKeyInvalid) return null;
  if (premiumKeyValid) {
    const upgradeLink = `${MailPoet.MailPoetComUrlFactory.getUpgradeUrl(
      pluginPartialKey,
      { s: subscribersCount, capabilities: { sendingService: true } },
    )}&utm_source=plugin&utm_medium=premium&utm_campaign=upgrade&ref=creator-sending-paused-message`;
    return (
      <Notice type="error" timeout={false} closable={false} renderInPlace>
        <h3>{MailPoet.I18n.t('allSendingPausedHeader')}</h3>
        <p>
          {ReactStringReplace(
            MailPoet.I18n.t('allSendingPausedPremiumValidBody'),
            /\[link\](.*?)\[\/link\]/g,
            (match, index) => {
              // first link tag
              if (index === 1) {
                return (
                  <a
                    href={upgradeLink}
                    target="_blank"
                    rel="noopener noreferrer"
                    key="purchase-plan"
                  >
                    {match}
                  </a>
                );
              }
              // second link tag
              return (
                <a href="?page=mailpoet-settings#premium" key="check-sending">
                  {match}
                </a>
              );
            },
          )}
        </p>
        <p>
          <a
            href={upgradeLink}
            className="button button-primary"
            target="_blank"
            rel="noopener noreferrer"
          >
            {MailPoet.I18n.t('allSendingPausedPremiumValidLink')}
          </a>
        </p>
      </Notice>
    );
  }
  // Premium key invalid
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

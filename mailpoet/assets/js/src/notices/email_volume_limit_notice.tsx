import ReactStringReplace from 'react-string-replace';
import ReactHtmlParser from 'react-html-parser';
import { MailPoet } from 'mailpoet';
import { Notice } from 'notices/notice';
import { withBoundary } from 'common';

function EmailVolumeLimitNotice(): JSX.Element {
  if (!MailPoet.emailVolumeLimitReached) return null;

  const title = MailPoet.I18n.t('emailVolumeLimitNoticeTitle').replace(
    '[emailVolumeLimit]',
    MailPoet.emailVolumeLimit,
  );
  const youReachedEmailVolumeLimit = MailPoet.I18n.t(
    'youReachedEmailVolumeLimit',
  ).replace('[emailVolumeLimit]', MailPoet.emailVolumeLimit);
  const upgradeLink = MailPoet.MailPoetComUrlFactory.getUpgradeUrl(
    MailPoet.pluginPartialKey,
  );
  const refreshSubscribers = async () => {
    await MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'services',
      action: 'recheckKeys',
    });
    window.location.reload();
  };

  const date = new Date();
  const firstDayOfTheNextMonth = new Date(
    date.getFullYear(),
    date.getMonth() + 1,
    1,
  );
  let toContinueUpgradeYourPlanOrWaitUntil = ReactStringReplace(
    MailPoet.I18n.t('toContinueUpgradeYourPlanOrWaitUntil'),
    /\[link](.*?)\[\/link]/g,
    (match) => (
      <a target="_blank" rel="noreferrer" href={upgradeLink} key={match}>
        {match}
      </a>
    ),
  );

  toContinueUpgradeYourPlanOrWaitUntil = ReactStringReplace(
    toContinueUpgradeYourPlanOrWaitUntil,
    /<b>\[date]<\/b>\./g,
    () =>
      ReactHtmlParser(`<b>${MailPoet.Date.short(firstDayOfTheNextMonth)}</b>.`),
  );

  return (
    <Notice type="error" timeout={false} closable={false} renderInPlace>
      <h3>{title}</h3>
      <p>
        {youReachedEmailVolumeLimit}
        <br />
        {toContinueUpgradeYourPlanOrWaitUntil}
      </p>
      <p>
        <a
          target="_blank"
          rel="noopener noreferrer"
          className="button button-primary"
          href={upgradeLink}
        >
          {MailPoet.I18n.t('upgradeNow')}
        </a>{' '}
        <button type="button" className="button" onClick={refreshSubscribers}>
          {MailPoet.I18n.t('refreshMyEmailVolumeLimit')}
        </button>
      </p>
    </Notice>
  );
}

EmailVolumeLimitNotice.displayName = 'EmailVolumeLimitNotice';
const EmailVolumeLimitNoticeWithBoundary = withBoundary(EmailVolumeLimitNotice);
export { EmailVolumeLimitNoticeWithBoundary as EmailVolumeLimitNotice };

import { extractPageNameFromUrl } from 'common/functions';
import { Notice } from 'notices/notice';
import { MailPoet } from 'mailpoet';
import ReactStringReplace from 'react-string-replace';
import { useServiceCheck } from './hooks/use-service-check';

function CronPingErrorNotice() {
  const cronError = useServiceCheck(
    'sendingQueue',
    'pingCron',
    () =>
      // Show only when sending method is MailPoet
      MailPoet.cronTriggerMethod === 'WordPress' &&
      // Do not show on the MailPoet Help page, it has its own MSS status indication
      extractPageNameFromUrl() !== 'help',
  );

  if (!cronError) return null;
  return (
    <Notice type="error" timeout={false} closable={false} renderInPlace>
      <h3>{MailPoet.I18n.t('cronPingErrorHeader')}</h3>
      <p>
        {ReactStringReplace(
          `${MailPoet.I18n.t(
            'systemStatusConnectionUnsuccessful',
          )} ${MailPoet.I18n.t('systemStatusCronConnectionUnsuccessfulInfo')}`,
          /\[link\](.*?)\[\/link\]/g,
          (match) => (
            <a
              href="https://kb.mailpoet.com/article/231-sending-does-not-work"
              target="_blank"
              rel="noopener noreferrer"
              key="cron-ping-error-kb-link"
            >
              {match}
            </a>
          ),
        )}
      </p>
      {cronError ? (
        <p>
          <i>{cronError}</i>
        </p>
      ) : null}
      <p>{MailPoet.I18n.t('systemStatusIntroCron')}</p>
    </Notice>
  );
}

CronPingErrorNotice.displayName = 'CronPingErrorNotice';
export { CronPingErrorNotice };

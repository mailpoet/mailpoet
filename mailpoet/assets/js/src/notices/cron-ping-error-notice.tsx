import { useState, useEffect } from 'react';
import { extractPageNameFromUrl } from 'common/functions';
import { Notice } from 'notices/notice';
import { MailPoet } from 'mailpoet';
import ReactStringReplace from 'react-string-replace';

const LOCAL_STORAGE_KEY = 'mailpoet_cron_ping_last_success_at';
const CACHE_TIME = 60 * 60 * 1000; // 1 hour

const cacheSuccessfulResult = () => {
  window.localStorage.setItem(LOCAL_STORAGE_KEY, Date.now().toString());
};

const isSuccessfulResultCached = () => {
  const lastSuccessAt = window.localStorage.getItem(LOCAL_STORAGE_KEY);
  return (
    lastSuccessAt && Date.now() - parseInt(lastSuccessAt, 10) <= CACHE_TIME
  );
};

function CronPingErrorNotice() {
  const [cronError, setCronError] = useState<string | null>(null);

  useEffect(() => {
    // Show only when cron trigger method is WordPress
    if (MailPoet.cronTriggerMethod !== 'WordPress') return;
    // Do not show on the MailPoet Help page, it has its own cron status indication
    if (extractPageNameFromUrl() === 'help') return;

    // Don't make a request if we have a recently cached successful result
    if (isSuccessfulResultCached()) return;

    void MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'sendingQueue',
      action: 'pingCron',
    })
      .done(() => {
        cacheSuccessfulResult();
        // Do not display the notice if there are no errors
        setCronError(null);
      })
      .fail((errorResponse) => {
        const errObject = errorResponse?.errors?.[0];
        if (errObject.error && errObject.message) {
          // Display cron error if server responded with error message
          setCronError(errObject.message);
        } else {
          // Do not display the notice if there is no server response
          setCronError(null);
        }
      });
  }, []);

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

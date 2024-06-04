import { useState, useEffect } from 'react';
import { extractPageNameFromUrl } from 'common/functions';
import { Notice } from 'notices/notice';
import { MailPoet } from 'mailpoet';
import ReactStringReplace from 'react-string-replace';

const LOCAL_STORAGE_KEY = 'mailpoet_mss_ping_last_success_at';
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

function BridgePingErrorNotice() {
  const [bridgeError, setBridgeError] = useState<string | null>(null);

  useEffect(() => {
    // Show only when sending method is MailPoet
    if (MailPoet.mtaMethod !== 'MailPoet') return;
    // Do not show on the MailPoet Help page, it has its own MSS status indication
    if (extractPageNameFromUrl() === 'help') return;

    // Don't make a request if we have a recently cached successful result
    if (isSuccessfulResultCached()) return;

    void MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'Services',
      action: 'pingBridge',
    })
      .done(() => {
        cacheSuccessfulResult();
        // Do not display the notice if there are no errors
        setBridgeError(null);
      })
      .fail((errorResponse) => {
        const errObject = errorResponse?.errors?.[0];
        if (errObject.error && errObject.message) {
          // Display MSS error if server responded with error message
          setBridgeError(errObject.message);
        } else {
          // Do not display the notice if there is no server response
          setBridgeError(null);
        }
      });
  }, []);

  if (!bridgeError) return null;
  return (
    <Notice type="error" timeout={false} closable={false} renderInPlace>
      <h3>{MailPoet.I18n.t('bridgePingErrorHeader')}</h3>
      <p>
        {ReactStringReplace(
          `${MailPoet.I18n.t('systemStatusMSSConnectionCanNotConnect')}`,
          /\[link\](.*?)\[\/link\]/g,
          (match) => (
            <a
              href="https://kb.mailpoet.com/article/319-known-errors-when-validating-a-mailpoet-key"
              target="_blank"
              rel="noopener noreferrer"
              key="bridge-ping-error-kb-link"
            >
              {match}
            </a>
          ),
        )}
      </p>
      {bridgeError ? (
        <p>
          <i>{bridgeError}</i>
        </p>
      ) : null}
    </Notice>
  );
}

BridgePingErrorNotice.displayName = 'BridgePingErrorNotice';
export { BridgePingErrorNotice };

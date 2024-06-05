import { extractPageNameFromUrl } from 'common/functions';
import { Notice } from 'notices/notice';
import { MailPoet } from 'mailpoet';
import ReactStringReplace from 'react-string-replace';
import { useServiceCheck } from './hooks/use-service-check';

function BridgePingErrorNotice() {
  const bridgeError = useServiceCheck(
    'Services',
    'pingBridge',
    () =>
      // Show only when sending method is MailPoet
      MailPoet.mtaMethod === 'MailPoet' &&
      // Do not show on the MailPoet Help page, it has its own MSS status indication
      extractPageNameFromUrl() !== 'help',
  );

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

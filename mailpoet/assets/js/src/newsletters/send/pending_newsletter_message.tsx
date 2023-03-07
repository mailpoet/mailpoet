import { __ } from '@wordpress/i18n';
import { MouseEvent, useCallback, useState } from 'react';
import ReactStringReplace from 'react-string-replace';
import { callApi, getLinkRegex, isTruthy, withBoundary } from 'common';
import { MailPoet } from 'mailpoet';

function PendingNewsletterMessage({
  toggleLoadingState,
  updatePendingState,
}: {
  toggleLoadingState: (loading: boolean) => void;
  updatePendingState: (isPending: boolean) => void;
}) {
  const refreshMssKeyState = useCallback(async () => {
    try {
      const { success, res } = await callApi<{
        result: { data: { is_approved: boolean | string | number } };
      }>({
        endpoint: 'services',
        action: 'refreshMSSKeyStatus',
      });

      if (success === true) {
        updatePendingState(!isTruthy(res.data.result.data.is_approved));
      } else {
        MailPoet.Notice.showApiErrorNotice(res);
      }
    } catch (error) {
      MailPoet.Notice.showApiErrorNotice(error);
    }
  }, [updatePendingState]);

  const [showRefreshButton, keepShowingRefresh] = useState(true);

  const recheckKey = async (e: MouseEvent<HTMLAnchorElement>) => {
    e.preventDefault();
    toggleLoadingState(true);
    await refreshMssKeyState();
    keepShowingRefresh(false);
    toggleLoadingState(false);
  };

  return (
    <div className="mailpoet_error">
      {ReactStringReplace(
        __(
          'You’ll soon be able to send once our team reviews your account. In the meantime, you can send previews to [link]your authorized emails[/link].',
          'mailpoet',
        ),
        getLinkRegex(),
        (match) => (
          <a
            href="https://account.mailpoet.com/authorization"
            target="_blank"
            rel="noopener noreferrer"
          >
            {match}
          </a>
        ),
      )}{' '}
      {showRefreshButton &&
        ReactStringReplace(
          __(
            'If you have already received approval email, click [link]here[/link] to update the status.',
            'mailpoet',
          ),
          getLinkRegex(),
          (match) => (
            <a href="#" onClick={recheckKey}>
              {match}
            </a>
          ),
        )}
    </div>
  );
}

PendingNewsletterMessage.displayName = 'PendingNewsletterMessage';

const PendingNewsletterMessageWithBoundary = withBoundary(
  PendingNewsletterMessage,
);
export { PendingNewsletterMessageWithBoundary as PendingNewsletterMessage };

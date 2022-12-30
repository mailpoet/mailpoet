import { MouseEvent, useCallback, useState } from 'react';
import ReactStringReplace from 'react-string-replace';
import { callApi, getLinkRegex, isTruthy, t, withBoundary } from 'common';
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
        t('pendingKeyApprovalNotice'),
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
          t('pendingKeyApprovalNoticeRefresh'),
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

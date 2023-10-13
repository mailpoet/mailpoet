import { MouseEvent, useCallback, useState } from 'react';
import { callApi, isTruthy, withBoundary } from 'common';
import { MailPoet } from 'mailpoet';
import {
  ClickToRefresh,
  PendingApprovalMessage,
} from '../../common/pending-approval-notice';

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
      <PendingApprovalMessage />
      {showRefreshButton && (
        <>
          <br />
          <br />
          <ClickToRefresh onRefreshClick={recheckKey} />
        </>
      )}
    </div>
  );
}

PendingNewsletterMessage.displayName = 'PendingNewsletterMessage';

const PendingNewsletterMessageWithBoundary = withBoundary(
  PendingNewsletterMessage,
);
export { PendingNewsletterMessageWithBoundary as PendingNewsletterMessage };

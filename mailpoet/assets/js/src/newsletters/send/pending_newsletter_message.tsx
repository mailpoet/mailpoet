import { MouseEvent, useState } from 'react';
import ReactStringReplace from 'react-string-replace';
import { getLinkRegex, t, withBoundary } from 'common';
import { useAction, useSelector } from '../../settings/store/hooks';

function PendingNewsletterMessage({
  toggleLoadingState,
  updatePendingState,
}: {
  toggleLoadingState: (loading: boolean) => void;
  updatePendingState: (approved: boolean) => void;
}) {
  const getKeyActivationState = useSelector('getKeyActivationState');
  const getSettings = useSelector('getSettings');
  const [showRefreshButton, keepShowingRefresh] = useState(true);
  //
  const verifyMssKey = useAction('verifyMssKey') as unknown as (
    key: string,
  ) => PromiseLike<void>;
  const recheckKey = async (e: MouseEvent<HTMLAnchorElement>) => {
    e.preventDefault();
    const state = getKeyActivationState();
    toggleLoadingState(true);
    await verifyMssKey(state.key);
    keepShowingRefresh(false);
    toggleLoadingState(false);
    updatePendingState(
      !getSettings().mta.mailpoet_api_key_state.data.is_approved,
    );
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

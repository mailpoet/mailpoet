import { MailPoet } from 'mailpoet';

// Same event and properties as the account page on account.mailpoet.com, so
// both show which review status customers saw.
const trackShownReviewStatus = (): void => {
  const status = document.querySelector<HTMLElement>(
    '[data-mailpoet-review-status]',
  );
  if (!status) {
    return;
  }
  MailPoet.trackEvent('Account review status shown', {
    public_subscription_id: status.dataset.publicSubscriptionId || null,
    status: status.dataset.mailpoetReviewStatus,
    reason: status.dataset.reason || null,
    since: status.dataset.since || null,
  });
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', trackShownReviewStatus);
} else {
  trackShownReviewStatus();
}

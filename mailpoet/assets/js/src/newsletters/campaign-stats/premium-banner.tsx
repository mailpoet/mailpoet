import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { Button } from 'common/button/button';
import { PremiumRequired } from 'common/premium-required/premium-required';
import { withBoundary } from '../../common';
import { PremiumBannerWithUpgrade } from '../../common/premium-banner-with-upgrade/premium-banner-with-upgrade';

function SkipDisplayingDetailedStats() {
  const ctaButton = (
    <Button
      href={MailPoet.MailPoetComUrlFactory.getPurchasePlanUrl(
        MailPoet.subscribersCount,
        MailPoet.currentWpUserEmail,
        'starter',
        { utm_medium: 'stats', utm_campaign: 'signup' },
      )}
      target="_blank"
      rel="noopener noreferrer"
    >
      {__('Upgrade', 'mailpoet')}
    </Button>
  );

  const description = (
    <p>
      {__(
        'Learn more about your subscribers and optimize your campaigns. See who opened your emails, which links they clicked, and then use the data to make your emails even better. And if you run a WooCommerce store, you’ll also see the revenue earned per email.',
        'mailpoet',
      )}{' '}
      <a href="admin.php?page=mailpoet-upgrade">
        {__('Learn more', 'mailpoet')}
      </a>
      .
    </p>
  );

  return (
    <div className="mailpoet-stats-premium-required">
      <PremiumBannerWithUpgrade
        message={description}
        actionButton={ctaButton}
        capabilityName="detailedAnalytics"
      />
    </div>
  );
}

function PremiumBanner() {
  if (!window.mailpoet_display_detailed_stats) {
    return <SkipDisplayingDetailedStats />;
  }
  if (window.mailpoet_subscribers_limit_reached) {
    const hasValidApiKey = window.mailpoet_has_valid_api_key;
    const title = __('Upgrade required', 'mailpoet');
    const youReachedTheLimit = hasValidApiKey
      ? __(
          'Congratulations, you now have [subscribersCount] subscribers! Your plan is limited to [subscribersLimit] subscribers. You need to upgrade now to be able to continue using MailPoet.',
          'mailpoet',
        )
      : __(
          'Congratulations, you now have [subscribersCount] subscribers! Our free version is limited to [subscribersLimit] subscribers. You need to upgrade now to be able to continue using MailPoet.',
          'mailpoet',
        )
          .replace('[subscribersLimit]', MailPoet.subscribersLimit.toString())
          .replace('[subscribersCount]', MailPoet.subscribersCount.toString());
    const upgradeLink = hasValidApiKey
      ? MailPoet.MailPoetComUrlFactory.getUpgradeUrl(MailPoet.pluginPartialKey)
      : MailPoet.MailPoetComUrlFactory.getPurchasePlanUrl(
          window.mailpoet_subscribers_count + 1,
        );

    return (
      <div className="mailpoet-stats-premium-required">
        <PremiumRequired
          title={title}
          message={<p>{youReachedTheLimit}</p>}
          actionButton={
            <Button
              target="_blank"
              rel="noopener noreferrer"
              href={upgradeLink}
            >
              {__('Upgrade Now', 'mailpoet')}
            </Button>
          }
        />
      </div>
    );
  }
  return null;
}

PremiumBanner.displayName = 'PremiumBanner';

const PremiumBannerWithBoundary = withBoundary(PremiumBanner);
export { PremiumBannerWithBoundary as PremiumBanner };

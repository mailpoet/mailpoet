import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { Button } from 'common/button/button';
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
  return null;
}

PremiumBanner.displayName = 'PremiumBanner';

const PremiumBannerWithBoundary = withBoundary(PremiumBanner);
export { PremiumBannerWithBoundary as PremiumBanner };

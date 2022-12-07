import { MailPoet } from 'mailpoet';
import { Button } from 'common/button/button';
import { PremiumRequired } from 'common/premium_required/premium_required';
import { withBoundary } from '../../common';

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
      {MailPoet.I18n.t('premiumBannerCtaFree')}
    </Button>
  );

  const description = (
    <p>
      {MailPoet.I18n.t('premiumBannerDescription')}{' '}
      <a href="admin.php?page=mailpoet-upgrade">
        {MailPoet.I18n.t('learnMore')}
      </a>
      .
    </p>
  );

  return (
    <div className="mailpoet-stats-premium-required">
      <PremiumRequired
        title={MailPoet.I18n.t('premiumFeature')}
        message={description}
        actionButton={ctaButton}
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
    const title = MailPoet.I18n.t('upgradeRequired');
    const youReachedTheLimit = MailPoet.I18n.t(
      hasValidApiKey ? 'newsletterYourPlanLimit' : 'newsletterFreeVersionLimit',
    )
      .replace('[subscribersLimit]', window.mailpoet_subscribers_limit)
      .replace('[subscribersCount]', window.mailpoet_subscribers_count);
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
              {MailPoet.I18n.t('upgradeNow')}
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

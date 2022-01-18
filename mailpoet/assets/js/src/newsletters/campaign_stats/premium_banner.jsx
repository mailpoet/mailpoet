import React from 'react';
import MailPoet from 'mailpoet';
import Button from 'common/button/button';
import PremiumBannerWithUpgrade from 'common/premium_banner_with_upgrade/premium_banner_with_upgrade';

const SkipDisplayingDetailedStats = () => {
  const ctaButton = (
    <Button
      href={MailPoet.MailPoetComUrlFactory.getFreePlanUrl({ utm_medium: 'stats', utm_campaign: 'signup' })}
      target="_blank"
      rel="noopener noreferrer"
    >
      {MailPoet.I18n.t('premiumBannerCtaFree')}
    </Button>
  );

  const description = (
    <p>
      {MailPoet.I18n.t('premiumBannerDescription')}
      {' '}
      <a href="admin.php?page=mailpoet-premium">
        {MailPoet.I18n.t('learnMore')}
      </a>
      .
    </p>
  );

  return (
    <div className="mailpoet-stats-premium-required">
      <PremiumBannerWithUpgrade
        message={description}
        actionButton={ctaButton}
      />
    </div>
  );
};

const PremiumBanner = () => {
  if (!window.mailpoet_display_detailed_stats) {
    return (
      <SkipDisplayingDetailedStats />
    );
  }
  return null;
};

export default PremiumBanner;

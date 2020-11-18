import React from 'react';
import MailPoet from 'mailpoet';
import Button from 'common/button/button';
import PremiumRequired from 'common/premium_required/premium_required';

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
      <PremiumRequired
        title={MailPoet.I18n.t('premiumFeature')}
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
  if (window.mailpoet_subscribers_limit_reached) {
    const hasValidApiKey = window.mailpoet_has_valid_api_key;
    const title = MailPoet.I18n.t('upgradeRequired');
    const youReachedTheLimit = MailPoet.I18n.t(hasValidApiKey ? 'newsletterYourPlanLimit' : 'newsletterFreeVersionLimit')
      .replace('[subscribersLimit]', window.mailpoet_subscribers_limit)
      .replace('[subscribersCount]', window.mailpoet_subscribers_count);
    const upgradeLink = hasValidApiKey
      ? 'https://account.mailpoet.com/upgrade'
      : `https://account.mailpoet.com/?s=${window.mailpoet_subscribers_count + 1}`;

    return (
      <div className="mailpoet-stats-premium-required">
        <PremiumRequired
          title={title}
          message={(<p>{youReachedTheLimit}</p>)}
          actionButton={(
            <Button
              target="_blank"
              rel="noopener noreferrer"
              href={upgradeLink}
            >
              {MailPoet.I18n.t('upgradeNow')}
            </Button>
          )}
        />
      </div>
    );
  }
  return null;
};

export default PremiumBanner;

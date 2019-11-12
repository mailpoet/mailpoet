import React from 'react';
import MailPoet from 'mailpoet';

const PremiumBanner = () => {
  if (window.mailpoet_display_detailed_stats) {
    return null;
  }

  let ctaButton = null;
  if (window.mailpoet_subscribers_count <= window.mailpoet_free_premium_subscribers_limit) {
    ctaButton = (
      <a
        className="button"
        href={MailPoet.MailPoetComUrlFactory.getFreePlanUrl({ utm_medium: 'stats', utm_campaign: 'signup' })}
        target="_blank"
        rel="noopener noreferrer"
      >
        {MailPoet.I18n.t('premiumBannerCtaFree')}
      </a>
    );
  } else {
    ctaButton = (
      <a
        className="button"
        href={MailPoet.MailPoetComUrlFactory.getPricingPageUrl(window.mailpoet_subscribers_count)}
        target="_blank"
        rel="noopener noreferrer"
      >
        {MailPoet.I18n.t('premiumBannerCtaPremium')}
      </a>
    );
  }

  return (
    <div className="mailpoet_stats_premium_banner">
      <h1>{MailPoet.I18n.t('premiumBannerTitle')}</h1>
      <p>{ctaButton}</p>
      <a href="admin.php?page=mailpoet-premium">{MailPoet.I18n.t('premiumBannerLink')}</a>
    </div>
  );
};

export default PremiumBanner;

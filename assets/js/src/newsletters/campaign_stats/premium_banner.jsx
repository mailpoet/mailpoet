import React from 'react';
import MailPoet from 'mailpoet';

const PremiumBanner = () => {
  if (!window.mailpoet_display_detailed_stats) {
    const ctaButton = (
      <a
        className="button"
        href={MailPoet.MailPoetComUrlFactory.getFreePlanUrl({ utm_medium: 'stats', utm_campaign: 'signup' })}
        target="_blank"
        rel="noopener noreferrer"
      >
        {MailPoet.I18n.t('premiumBannerCtaFree')}
      </a>
    );

    return (
      <div className="mailpoet_stats_premium_banner">
        <h1>{MailPoet.I18n.t('premiumBannerTitle')}</h1>
        <p>{ctaButton}</p>
        <a href="admin.php?page=mailpoet-premium">{MailPoet.I18n.t('premiumBannerLink')}</a>
      </div>
    );
  }
  if (window.mailpoet_subscribers_limit_reached) {
    const hasValidApiKey = window.mailpoet_has_valid_api_key;
    const title = MailPoet.I18n.t('subscribersLimitNoticeTitle')
      .replace('[subscribersLimit]', window.mailpoet_subscribers_limit);
    const youReachedTheLimit = MailPoet.I18n.t(hasValidApiKey ? 'yourPlanLimit' : 'freeVersionLimit')
      .replace('[subscribersLimit]', window.mailpoet_subscribers_limit);
    const upgradeLink = hasValidApiKey
      ? 'https://account.mailpoet.com/upgrade'
      : `https://account.mailpoet.com/?s=${window.mailpoet_subscribers_count + 1}`;

    return (
      <div className="mailpoet_stats_premium_banner">
        <h1>{title}</h1>
        <p>{youReachedTheLimit}</p>
        <p>
          <a
            target="_blank"
            rel="noopener noreferrer"
            className="button"
            href={upgradeLink}
          >
            {MailPoet.I18n.t('upgradeNow')}
          </a>
        </p>
      </div>
    );
  }
  return null;
};

export default PremiumBanner;

import React from 'react';
import MailPoet from 'mailpoet';
import addReferralId from 'referral_url_decorator.jsx';

const PremiumBanner = () => {
  if (window.mailpoet_premium_active) {
    return null;
  }

  let ctaButton = null;
  if (window.mailpoet_subscribers_count <= window.mailpoet_free_premium_subscribers_limit) {
    ctaButton = (
      <a
        className="button"
        href={addReferralId('https://www.mailpoet.com/free-plan/')}
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
        href={addReferralId(`https://www.mailpoet.com/pricing/?subscribers=${window.mailpoet_subscribers_count}`)}
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

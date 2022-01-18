import React from 'react';
import MailPoet from 'mailpoet';
import PremiumRequired from 'common/premium_required/premium_required';
import Button from 'common/button/button';
import ReactStringReplace from 'react-string-replace';

type Props = {
  message: React.ReactNode;
  actionButton: React.ReactNode;
};

interface BannerWindow extends Window {
  mailpoet_has_valid_api_key: boolean;
  mailpoet_has_valid_premium_key: boolean;
  mailpoet_subscribers_count: number;
  mailpoet_subscribers_limit: number | boolean;
  mailpoet_subscribers_limit_reached: boolean;
  mailpoet_premium_active: boolean;
  mailpoet_premium_plugin_installed: boolean;
  mailpoet_premium_plugin_download_url: string;
  mailpoet_premium_plugin_activation_url: string;
}

declare let window: BannerWindow;

const limitReached = window.mailpoet_subscribers_limit_reached;
const limitValue = window.mailpoet_subscribers_limit;
const subscribersCountTowardsLimit = window.mailpoet_subscribers_count;
const premiumActive = window.mailpoet_premium_active;
const hasValidApiKey = window.mailpoet_has_valid_api_key || window.mailpoet_has_valid_premium_key;
const downloadUrl = window.mailpoet_premium_plugin_download_url;
const activationUrl = window.mailpoet_premium_plugin_activation_url;
const isPremiumPluginInstalled = window.mailpoet_premium_plugin_installed;

const getBannerMessage = (translationKey) => {
  const message = MailPoet.I18n.t(translationKey);
  return (
    <p>
      {ReactStringReplace(
        message,
        /(\[subscribersCount]|\[subscribersLimit])/g,
        (match) => ((match === '[subscribersCount]') ? subscribersCountTowardsLimit : limitValue)
      )}
    </p>
  );
};

const getCtaButton = (translationKey, link = null, target = '_blank') => (
  <Button
    href={link}
    target={target}
    rel="noopener noreferrer"
  >
    {MailPoet.I18n.t(translationKey)}
  </Button>
);

const PremiumBannerWithUpgrade: React.FunctionComponent<Props> = (
  { message, actionButton }: Props
) => {
  let bannerMessage: React.ReactNode;
  let ctaButton: React.ReactNode;

  if (hasValidApiKey && !premiumActive) {
    bannerMessage = getBannerMessage('premiumFeatureDescription');

    ctaButton = isPremiumPluginInstalled
      ? getCtaButton('premiumFeatureButtonActivatePremium', activationUrl, '_self')
      : getCtaButton('premiumFeatureButtonDownloadPremium', downloadUrl);
  } else if (limitReached) {
    bannerMessage = getBannerMessage('premiumFeatureDescriptionSubscribersLimitReached');

    const link = hasValidApiKey
      ? MailPoet.MailPoetComUrlFactory.getUpgradeUrl()
      : MailPoet.MailPoetComUrlFactory.getPurchasePlanUrl(subscribersCountTowardsLimit + 1);

    ctaButton = getCtaButton('premiumFeatureButtonUpgradePlan', link);
  } else {
    // use the provided information
    bannerMessage = message;
    ctaButton = actionButton;
  }

  return (
    <PremiumRequired
      title={MailPoet.I18n.t('premiumFeature')}
      message={bannerMessage}
      actionButton={ctaButton}
    />
  );
};

export default PremiumBannerWithUpgrade;

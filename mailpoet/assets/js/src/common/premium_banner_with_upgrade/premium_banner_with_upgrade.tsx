import { ReactNode } from 'react';
import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { PremiumRequired } from 'common/premium_required/premium_required';
import { Button } from 'common/button/button';
import ReactStringReplace from 'react-string-replace';

type Props = {
  message: ReactNode;
  actionButton: ReactNode;
};

const {
  subscribersLimitReached,
  subscribersLimit,
  subscribersCount,
  premiumActive,
  hasValidApiKey,
  hasValidPremiumKey,
  isPremiumPluginInstalled,
  premiumPluginDownloadUrl,
  premiumPluginActivationUrl,
  pluginPartialKey,
} = MailPoet;

const anyValidKey = hasValidApiKey || hasValidPremiumKey;

const getBannerMessage = (translationKey: string) => {
  const message = MailPoet.I18n.t(translationKey);
  return (
    <p>
      {ReactStringReplace(
        message,
        /(\[subscribersCount]|\[subscribersLimit])/g,
        (match) =>
          match === '[subscribersCount]' ? subscribersCount : subscribersLimit,
      )}
    </p>
  );
};

const getCtaButton = (
  translationKey: string,
  link: string,
  target = '_blank',
) => (
  <Button href={link} target={target} rel="noopener noreferrer">
    {MailPoet.I18n.t(translationKey)}
  </Button>
);

export function PremiumBannerWithUpgrade({
  message,
  actionButton,
}: Props): JSX.Element {
  let bannerMessage: ReactNode;
  let ctaButton: ReactNode;

  if (anyValidKey && !premiumActive) {
    bannerMessage = getBannerMessage('premiumFeatureDescription');

    ctaButton = isPremiumPluginInstalled
      ? getCtaButton(
          'premiumFeatureButtonActivatePremium',
          premiumPluginActivationUrl,
          '_self',
        )
      : getCtaButton(
          'premiumFeatureButtonDownloadPremium',
          premiumPluginDownloadUrl,
        );
  } else if (subscribersLimitReached) {
    bannerMessage = getBannerMessage(
      'premiumFeatureDescriptionSubscribersLimitReached',
    );

    const link: string = anyValidKey
      ? MailPoet.MailPoetComUrlFactory.getUpgradeUrl(pluginPartialKey)
      : MailPoet.MailPoetComUrlFactory.getPurchasePlanUrl(
          +subscribersCount + 1,
        );

    ctaButton = getCtaButton('premiumFeatureButtonUpgradePlan', link);
  } else {
    // use the provided information
    bannerMessage = message;
    ctaButton = actionButton;
  }

  return (
    <PremiumRequired
      title={__('This is a Premium feature', 'mailpoet')}
      message={bannerMessage}
      actionButton={ctaButton}
    />
  );
}

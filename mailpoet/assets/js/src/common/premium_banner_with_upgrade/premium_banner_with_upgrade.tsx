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

const getBannerMessage = (message: string) => (
  <p>
    {ReactStringReplace(
      message,
      /(\[subscribersCount]|\[subscribersLimit])/g,
      (match) =>
        match === '[subscribersCount]' ? subscribersCount : subscribersLimit,
    )}
  </p>
);

const getCtaButton = (message: string, link: string, target = '_blank') => (
  <Button href={link} target={target} rel="noopener noreferrer">
    {message}
  </Button>
);

export function PremiumBannerWithUpgrade({
  message,
  actionButton,
}: Props): JSX.Element {
  let bannerMessage: ReactNode;
  let ctaButton: ReactNode;

  if (anyValidKey && !premiumActive) {
    bannerMessage = getBannerMessage(
      __(
        'Your current MailPoet plan includes advanced features, but they require the MailPoet Premium plugin to be installed and activated.',
        'mailpoet',
      ),
    );

    ctaButton = isPremiumPluginInstalled
      ? getCtaButton(
          __('Activate MailPoet Premium plugin', 'mailpoet'),
          premiumPluginActivationUrl,
          '_self',
        )
      : getCtaButton(
          __('Download MailPoet Premium plugin', 'mailpoet'),
          premiumPluginDownloadUrl,
        );
  } else if (subscribersLimitReached) {
    bannerMessage = getBannerMessage(
      __(
        'Congratulations, you now have [subscribersCount] subscribers! Your plan is limited to [subscribersLimit] subscribers. You need to upgrade now to be able to continue using MailPoet.',
        'mailpoet',
      ),
    );

    const link: string = anyValidKey
      ? MailPoet.MailPoetComUrlFactory.getUpgradeUrl(pluginPartialKey)
      : MailPoet.MailPoetComUrlFactory.getPurchasePlanUrl(
          +subscribersCount + 1,
        );

    ctaButton = getCtaButton(__('Upgrade your plan', 'mailpoet'), link);
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

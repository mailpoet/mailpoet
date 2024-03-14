import { ReactNode } from 'react';
import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { PremiumRequired } from 'common/premium-required/premium-required';
import { Button } from 'common/button/button';
import ReactStringReplace from 'react-string-replace';
import { PremiumMessageWithModal } from 'common/premium-key/key-messages';

type Props = {
  message: ReactNode;
  actionButton: ReactNode;
  capabilityName?: string;
};

const {
  subscribersLimitReached,
  subscribersLimit,
  subscribersCount,
  premiumActive,
  hasValidApiKey,
  hasValidPremiumKey,
  isPremiumPluginInstalled,
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

const getPremiumCtaButton = (buttonText: string) => (
  <PremiumMessageWithModal buttonText={buttonText} />
);

export function PremiumBannerWithUpgrade({
  message,
  actionButton,
  capabilityName = undefined,
}: Props): JSX.Element {
  let bannerMessage: ReactNode;
  let ctaButton: ReactNode;

  let title = __('This is a Premium feature', 'mailpoet');

  if (hasValidPremiumKey && (!isPremiumPluginInstalled || !premiumActive)) {
    bannerMessage = getBannerMessage(
      __(
        'Your current MailPoet plan includes advanced features, but they require the MailPoet Premium plugin to be installed and activated.',
        'mailpoet',
      ),
    );

    ctaButton = isPremiumPluginInstalled
      ? getPremiumCtaButton(__('Activate MailPoet Premium plugin', 'mailpoet'))
      : getPremiumCtaButton(__('Download MailPoet Premium plugin', 'mailpoet'));
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
          null,
          null,
          null,
        );

    ctaButton = getCtaButton(__('Upgrade your plan', 'mailpoet'), link);
  } else if (
    hasValidPremiumKey &&
    capabilityName &&
    MailPoet.capabilities[capabilityName].isRestricted
  ) {
    title = __('Upgrade your plan', 'mailpoet');
    bannerMessage = message;
    const link = MailPoet.MailPoetComUrlFactory.getUpgradeUrl(
      pluginPartialKey,
      { capability: capabilityName, s: subscribersCount },
    );
    ctaButton = getCtaButton(__('Upgrade', 'mailpoet'), link);
  } else {
    // use the provided information
    bannerMessage = message;
    ctaButton = actionButton;
  }

  return (
    <PremiumRequired
      title={title}
      message={bannerMessage}
      actionButton={ctaButton}
    />
  );
}

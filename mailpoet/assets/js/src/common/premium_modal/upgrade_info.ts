import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';

const {
  currentWpUserEmail,
  MailPoetComUrlFactory: { getPurchasePlanUrl, getUpgradeUrl },
  subscribersLimitReached,
  subscribersCount,
  premiumActive,
  hasValidApiKey,
  hasValidPremiumKey,
  isPremiumPluginInstalled,
  premiumPluginDownloadUrl,
  premiumPluginActivationUrl,
  pluginPartialKey,
} = MailPoet;

export const premiumFeaturesEnabled =
  hasValidPremiumKey && !subscribersLimitReached;

type UpgradeInfo = {
  title: string;
  info: string;
  cta: string;
  url: string;
};

type UtmParams = {
  utm_medium?: string;
  utm_campaign?: string;
};

// See: https://mailpoet.atlassian.net/browse/MAILPOET-4416
export const getUpgradeInfo = (
  utmParams: UtmParams = undefined,
): UpgradeInfo => {
  const utm = utmParams ? { utm_source: 'plugin', ...utmParams } : undefined;

  // a. User doesn't have a valid license.
  if (!hasValidPremiumKey && !hasValidApiKey) {
    return {
      title: __('Purchase a MailPoet plan', 'mailpoet'),
      info: __('Please purchase a MailPoet plan.', 'mailpoet'),
      cta: __('Purchase', 'mailpoet'),
      url: getPurchasePlanUrl(
        subscribersCount,
        currentWpUserEmail,
        'business',
        utm,
      ),
    };
  }

  // b. User has a license but is not eligible for premium features.
  if (!hasValidPremiumKey && hasValidApiKey) {
    return {
      title: __('Upgrade your MailPoet plan', 'mailpoet'),
      info: __('Please upgrade your MailPoet plan.', 'mailpoet'),
      cta: __('Upgrade', 'mailpoet'),
      url: getUpgradeUrl(pluginPartialKey),
    };
  }

  // c. User has a license, but they reached the subscribers limit.
  if (subscribersLimitReached) {
    return {
      title: __('Subscribers limit reached', 'mailpoet'),
      info: __(
        'Please upgrade your MailPoet plan to continue using MailPoet.',
        'mailpoet',
      ),
      cta: __('Upgrade', 'mailpoet'),
      url: getUpgradeUrl(pluginPartialKey),
    };
  }

  // d. User is eligible for premium features but doesn't have the premium plugin downloaded.
  if (hasValidPremiumKey && !isPremiumPluginInstalled) {
    return {
      title: __('Download the MailPoet Premium plugin', 'mailpoet'),
      info: __('Please download the MailPoet Premium plugin.', 'mailpoet'),
      cta: __('Download', 'mailpoet'),
      url: premiumPluginDownloadUrl,
    };
  }

  // e. User is eligible for premium features but doesn't have the premium plugin activated.
  if (hasValidPremiumKey && !premiumActive) {
    return {
      title: __('Activate the MailPoet Premium plugin', 'mailpoet'),
      info: __('Please activate the MailPoet Premium plugin.', 'mailpoet'),
      cta: __('Activate', 'mailpoet'),
      url: premiumPluginActivationUrl,
    };
  }

  // f. All of the above conditions were met, the premium plugin is already active.
  return {
    title: __('MailPoet Premium is active', 'mailpoet'),
    info: __(
      'The MailPoet Premium plugin was activated. Please reload the page.',
      'mailpoet',
    ),
    cta: __('Reload the page', 'mailpoet'),
    url: window.location.href,
  };
};

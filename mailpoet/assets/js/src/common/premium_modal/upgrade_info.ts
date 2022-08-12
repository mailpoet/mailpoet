import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { useState } from 'react';

const {
  currentWpUserEmail,
  MailPoetComUrlFactory: { getPurchasePlanUrl, getUpgradeUrl },
  subscribersLimitReached,
  subscribersCount,
  premiumActive,
  hasValidApiKey,
  hasValidPremiumKey,
  isPremiumPluginInstalled,
  pluginPartialKey,
  premiumPluginActivationUrl,
} = MailPoet;

// allow updating installed state to refresh upgrade info
let isPremiumInstalled = isPremiumPluginInstalled;

export const premiumFeaturesEnabled =
  hasValidPremiumKey && !subscribersLimitReached;

export type UpgradeInfo = {
  title: string;
  info: string;
  cta: string;
  action:
    | string
    | {
        handler: () => Promise<unknown>;
        busy: string;
        success: string;
        successHandler: () => void;
        error: string;
      };
};

export type UtmParams = {
  utm_medium?: string;
  utm_campaign?: string;
};

const requestPremiumApi = async (action: string): Promise<unknown> =>
  MailPoet.Ajax.post({
    api_version: MailPoet.apiVersion,
    endpoint: 'premium',
    action,
  });

// See: https://mailpoet.atlassian.net/browse/MAILPOET-4416
export const getUpgradeInfo = (
  utmParams: UtmParams = undefined,
  onPremiumInstalled?: () => void,
): UpgradeInfo => {
  const utm = utmParams ? { utm_source: 'plugin', ...utmParams } : undefined;

  // a. User doesn't have a valid license.
  if (!hasValidPremiumKey && !hasValidApiKey) {
    return {
      title: __('Purchase a MailPoet plan', 'mailpoet'),
      info: __('Please purchase a MailPoet plan.', 'mailpoet'),
      cta: __('Purchase', 'mailpoet'),
      action: getPurchasePlanUrl(
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
      action: getUpgradeUrl(pluginPartialKey),
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
      action: getUpgradeUrl(pluginPartialKey),
    };
  }

  // d. User is eligible for premium features but doesn't have the premium plugin downloaded.
  if (hasValidPremiumKey && !isPremiumInstalled) {
    return {
      title: __('Download the MailPoet Premium plugin', 'mailpoet'),
      info: __('Please download the MailPoet Premium plugin.', 'mailpoet'),
      cta: __('Download', 'mailpoet'),
      action: {
        handler: async () => {
          await requestPremiumApi('installPlugin');
          isPremiumInstalled = true;
          onPremiumInstalled();
        },
        busy: __('Downloading…', 'mailpoet'),
        success: __('Activate', 'mailpoet'),
        successHandler: () =>
          window.open(premiumPluginActivationUrl, '_blank').focus(),
        error: __('Plugin installation failed.', 'mailpoet'),
      },
    };
  }

  // e. User is eligible for premium features but doesn't have the premium plugin activated.
  if (hasValidPremiumKey && !premiumActive) {
    return {
      title: __('Activate the MailPoet Premium plugin', 'mailpoet'),
      info: __('Please activate the MailPoet Premium plugin.', 'mailpoet'),
      cta: __('Activate', 'mailpoet'),
      action: {
        handler: async () => requestPremiumApi('activatePlugin'),
        busy: __('Activating…', 'mailpoet'),
        success: __('Reload the page', 'mailpoet'),
        successHandler: () => window.location.reload(),
        error: __('Plugin activation failed.', 'mailpoet'),
      },
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
    action: window.location.href,
  };
};

export const useUpgradeInfo = (
  utmParams: UtmParams = undefined,
): UpgradeInfo => {
  // update info via "onInstalled" callback when premium plugin is installed
  const [info, setInfo] = useState(() => {
    const onInstalled = () => setInfo(getUpgradeInfo(utmParams, onInstalled));
    return getUpgradeInfo(utmParams, onInstalled);
  });
  return info;
};

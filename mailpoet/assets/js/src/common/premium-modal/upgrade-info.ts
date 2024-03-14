import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { useState } from 'react';

const {
  currentWpUserEmail,
  MailPoetComUrlFactory: { getPurchasePlanUrl, getUpgradeUrl },
  premiumPluginActivationUrl,
} = MailPoet;

export const premiumFeaturesEnabled =
  MailPoet.hasValidPremiumKey && !MailPoet.subscribersLimitReached;

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

export type Data = {
  premiumInstalled?: boolean;
  premiumActive?: boolean;
  hasValidApiKey?: boolean;
  hasValidPremiumKey?: boolean;
  pluginPartialKey?: string;
  subscribersCount?: number;
  subscribersLimitReached?: boolean;
  capabilityName?: string;
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
  {
    premiumInstalled = MailPoet.isPremiumPluginInstalled,
    premiumActive = MailPoet.premiumActive,
    hasValidApiKey = MailPoet.hasValidApiKey,
    hasValidPremiumKey = !!MailPoet.hasValidPremiumKey,
    pluginPartialKey = MailPoet.pluginPartialKey,
    subscribersCount = MailPoet.subscribersCount,
    subscribersLimitReached = MailPoet.subscribersLimitReached,
    capabilityName = undefined,
  }: Data = {},
  utmParams: UtmParams = undefined,
  onPremiumInstalled?: () => void,
): UpgradeInfo => {
  const utm = utmParams ? { utm_source: 'plugin', ...utmParams } : undefined;
  const upgradeParams = capabilityName
    ? { capability: capabilityName, s: subscribersCount }
    : {};
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
      action: getUpgradeUrl(pluginPartialKey, upgradeParams),
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
      action: getUpgradeUrl(pluginPartialKey, upgradeParams),
    };
  }

  // d. User is eligible for premium features but doesn't have the premium plugin downloaded.
  if (hasValidPremiumKey && !premiumInstalled) {
    return {
      title: __('Download the MailPoet Premium plugin', 'mailpoet'),
      info: __('Please download the MailPoet Premium plugin.', 'mailpoet'),
      cta: __('Download', 'mailpoet'),
      action: {
        handler: async () => {
          await requestPremiumApi('installPlugin');
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

  // f. User has a license but the feature is not available for the plan.
  if (capabilityName && MailPoet.capabilities[capabilityName]?.isRestricted) {
    let info: string;

    switch (capabilityName) {
      case 'detailedAnalytics':
        info = __(
          'Upgrade your MailPoet plan to gain detailed insights into how your subscribers engage with your automations and their purchasing behaviors.',
          'mailpoet',
        );
        break;
      case 'automationSteps':
        info = __(
          'Automation journeys are not available in your current plan. Upgrade your MailPoet plan to design personalized journeys with multiple steps and conditional branching logic.',
          'mailpoet',
        );
        break;
      case 'segmentFilters':
        info = __(
          'Advanced contact segmentation is not available in your current plan. Upgrade your MailPoet plan to create highly targeted subscriber segments using multiple subscriber properties and AND/OR logic, ensuring you send the right message to the right people.',
          'mailpoet',
        );
        break;
      default:
        info = __(
          'Please upgrade your MailPoet plan to gain access to this feature.',
          'mailpoet',
        );
    }

    return {
      title: __('Upgrade your MailPoet plan', 'mailpoet'),
      info,
      cta: __('Upgrade', 'mailpoet'),
      action: getUpgradeUrl(pluginPartialKey, upgradeParams),
    };
  }

  // g. All of the above conditions were met, the premium plugin is already active.
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
  data: Data = {},
  utmParams: UtmParams = undefined,
): UpgradeInfo => {
  // update info via "onInstalled" callback when premium plugin is installed
  const [info, setInfo] = useState(() => {
    const onInstalled = () =>
      setInfo(
        getUpgradeInfo(
          { ...data, premiumInstalled: true },
          utmParams,
          onInstalled,
        ),
      );
    return getUpgradeInfo(data, utmParams, onInstalled);
  });
  return info;
};

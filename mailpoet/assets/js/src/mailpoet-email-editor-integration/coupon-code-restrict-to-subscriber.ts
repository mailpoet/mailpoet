import {
  getBlockType,
  registerBlockType,
  unregisterBlockType,
} from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';

export const COUPON_CODE_BLOCK_NAME = 'woocommerce/coupon-code';
export const RESTRICT_TO_SUBSCRIBER_ATTRIBUTE = 'restrictToSubscriber';

export type CouponCodeAttributes = Record<string, unknown> & {
  source?: string;
  restrictToSubscriber?: boolean;
};

type RestrictToSubscriberVisibility = {
  attributes?: CouponCodeAttributes;
  blockName: string;
  isAutomationNewsletter?: boolean;
  isFeatureAvailable?: boolean;
};

export const isMailPoetCouponGenerationAvailable = (): boolean =>
  Boolean(
    window.mailpoet_is_automation_newsletter &&
      window.mailpoet_gutenberg_coupon_generation_available,
  );

export const addRestrictToSubscriberAttribute = (
  settings: BlockConfiguration<Record<string, unknown>>,
  blockName: string,
  isFeatureAvailable = isMailPoetCouponGenerationAvailable(),
): BlockConfiguration<Record<string, unknown>> => {
  if (blockName !== COUPON_CODE_BLOCK_NAME || !isFeatureAvailable) {
    return settings;
  }

  return {
    ...settings,
    attributes: {
      ...settings.attributes,
      [RESTRICT_TO_SUBSCRIBER_ATTRIBUTE]: {
        type: 'boolean',
        default: false,
      },
    },
  };
};

export const ensureRestrictToSubscriberAttributeRegistered = (
  isFeatureAvailable = isMailPoetCouponGenerationAvailable(),
): void => {
  if (!isFeatureAvailable) {
    return;
  }

  const settings = getBlockType(COUPON_CODE_BLOCK_NAME) as
    | BlockConfiguration<Record<string, unknown>>
    | undefined;

  if (!settings || settings.attributes?.[RESTRICT_TO_SUBSCRIBER_ATTRIBUTE]) {
    return;
  }

  unregisterBlockType(COUPON_CODE_BLOCK_NAME);
  const settingsWithAttribute = addRestrictToSubscriberAttribute(
    settings,
    COUPON_CODE_BLOCK_NAME,
    true,
  ) as unknown as Parameters<typeof registerBlockType>[1];
  registerBlockType(COUPON_CODE_BLOCK_NAME, settingsWithAttribute);
};

export const ensureRestrictToSubscriberAttributeRegisteredWhenAvailable = (
  attempts = 20,
): void => {
  ensureRestrictToSubscriberAttributeRegistered();

  const settings = getBlockType(COUPON_CODE_BLOCK_NAME);
  if (
    attempts <= 0 ||
    settings?.attributes?.[RESTRICT_TO_SUBSCRIBER_ATTRIBUTE]
  ) {
    return;
  }

  window.setTimeout(
    () =>
      ensureRestrictToSubscriberAttributeRegisteredWhenAvailable(attempts - 1),
    250,
  );
};

export const shouldShowRestrictToSubscriberControl = ({
  attributes,
  blockName,
  isAutomationNewsletter = Boolean(window.mailpoet_is_automation_newsletter),
  isFeatureAvailable = Boolean(
    window.mailpoet_gutenberg_coupon_generation_available,
  ),
}: RestrictToSubscriberVisibility): boolean =>
  blockName === COUPON_CODE_BLOCK_NAME &&
  isAutomationNewsletter &&
  isFeatureAvailable &&
  attributes?.source === 'createNew';

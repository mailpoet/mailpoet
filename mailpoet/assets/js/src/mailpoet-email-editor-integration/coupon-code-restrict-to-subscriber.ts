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
};

const hasGeneratedCouponSource = (
  settings: BlockConfiguration<Record<string, unknown>>,
): boolean => Boolean(settings.attributes?.source);

export const addRestrictToSubscriberAttribute = (
  settings: BlockConfiguration<Record<string, unknown>>,
  blockName: string,
): BlockConfiguration<Record<string, unknown>> => {
  if (
    blockName !== COUPON_CODE_BLOCK_NAME ||
    !hasGeneratedCouponSource(settings)
  ) {
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

export const ensureRestrictToSubscriberAttributeRegistered = (): boolean => {
  const settings = getBlockType(COUPON_CODE_BLOCK_NAME) as
    | BlockConfiguration<Record<string, unknown>>
    | undefined;

  if (!settings) {
    return false;
  }

  if (
    !hasGeneratedCouponSource(settings) ||
    settings.attributes?.[RESTRICT_TO_SUBSCRIBER_ATTRIBUTE]
  ) {
    return true;
  }

  // Apply the same settings change if WooCommerce registered the block first.
  unregisterBlockType(COUPON_CODE_BLOCK_NAME);
  const settingsWithAttribute = addRestrictToSubscriberAttribute(
    settings,
    COUPON_CODE_BLOCK_NAME,
  ) as unknown as Parameters<typeof registerBlockType>[1];
  registerBlockType(COUPON_CODE_BLOCK_NAME, settingsWithAttribute);
  return true;
};

export const ensureRestrictToSubscriberAttributeRegisteredWhenAvailable = (
  attempts = 20,
): void => {
  if (attempts <= 0 || ensureRestrictToSubscriberAttributeRegistered()) {
    return;
  }

  setTimeout(
    () =>
      ensureRestrictToSubscriberAttributeRegisteredWhenAvailable(attempts - 1),
    250,
  );
};

export const shouldShowRestrictToSubscriberControl = ({
  attributes,
  blockName,
  isAutomationNewsletter = Boolean(window.mailpoet_is_automation_newsletter),
}: RestrictToSubscriberVisibility): boolean =>
  blockName === COUPON_CODE_BLOCK_NAME &&
  isAutomationNewsletter &&
  attributes?.source === 'createNew';

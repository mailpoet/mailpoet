import { initialize as initializeCoreIntegration } from '../integrations/core';
import { initialize as initializeMailPoetIntegration } from '../integrations/mailpoet';
import { initialize as initializeWordPressIntegration } from '../integrations/wordpress';
import { initialize as initializeWooCommerceIntegration } from '../integrations/woocommerce';
import { initialize as initializeWooCommerceSubscriptionsIntegration } from '../integrations/woocommerce-subscriptions';

export function initializeIntegrations() {
  initializeCoreIntegration();
  initializeMailPoetIntegration();
  initializeWordPressIntegration();
  initializeWooCommerceIntegration();
  initializeWooCommerceSubscriptionsIntegration();
}

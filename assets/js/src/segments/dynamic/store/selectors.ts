import {
  AnyFormItem,
  StateType,
  WindowCustomFields,
  WindowEditableRoles,
  WindowNewslettersList,
  WindowProductCategories,
  WindowProducts,
  WindowSubscriptionProducts,
  WindowWooCommerceCountries,
} from '../types';

export const getProducts = (state: StateType): WindowProducts => (
  state.products
);
export const getSubscriptionProducts = (state: StateType): WindowSubscriptionProducts => (
  state.subscriptionProducts
);
export const getWordpressRoles = (state: StateType): WindowEditableRoles => (
  state.wordpressRoles
);
export const getProductCategories = (state: StateType): WindowProductCategories => (
  state.productCategories
);
export const getNewslettersList = (state: StateType): WindowNewslettersList => (
  state.newslettersList
);
export const canUseWooSubscriptions = (state: StateType): boolean => (
  state.canUseWooSubscriptions
);
export const getWooCommerceCurrencySymbol = (state: StateType): string => (
  state.wooCurrencySymbol
);
export const getWooCommerceCountries = (state: StateType): WindowWooCommerceCountries => (
  state.wooCountries
);
export const getCustomFieldsList = (state: StateType): WindowCustomFields => (
  state.customFieldsList
);
export const getSegment = (state: StateType): AnyFormItem => (
  state.segment
);

import {
  AnyFormItem, FilterValue, GroupFilterValue,
  StateType, SubscriberActionTypes,
  WindowCustomFields,
  WindowEditableRoles,
  WindowNewslettersList,
  WindowProductCategories,
  WindowProducts,
  WindowSubscriptionProducts,
  WindowWooCommerceCountries,
} from '../types';
import { SubscriberSegmentOptions } from '../dynamic_segments_filters/subscriber';

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
export const getErrors = (state: StateType): string[] => (
  state.errors
);
export const getAvailableFilters = (state: StateType): GroupFilterValue[] => (
  state.allAvailableFilters
);
export const findFilterValueForSegment = (
  state: StateType,
  itemSearch: AnyFormItem
): FilterValue | undefined => {
  let found: FilterValue | undefined;
  if (itemSearch.action === undefined) {
    // bc compatibility, the wordpress user role segment doesn't have action
    return SubscriberSegmentOptions.find(
      (value) => value.value === SubscriberActionTypes.WORDPRESS_ROLE
    );
  }

  state.allAvailableFilters.forEach((filter: GroupFilterValue) => {
    filter.options.forEach((option: FilterValue) => {
      if (option.group === itemSearch.segmentType) {
        if (itemSearch.action === option.value) {
          found = option;
        }
      }
    });
  });
  return found;
};

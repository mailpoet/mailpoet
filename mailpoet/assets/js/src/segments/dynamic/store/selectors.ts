import {
  AnyFormItem,
  FilterRow,
  FilterValue,
  GroupFilterValue,
  Segment,
  SignupForm,
  StateType,
  StaticSegment,
  SubscriberCount,
  Tag,
  WindowCustomFields,
  WindowEditableRoles,
  WindowMembershipPlans,
  WindowNewslettersList,
  WindowProductCategories,
  WindowProducts,
  WindowSubscriptionProducts,
  WindowWooCommerceCountries,
  WooPaymentMethod,
  WooShippingMethod,
} from '../types';

export const getProducts = (state: StateType): WindowProducts => state.products;
export const getMembershipPlans = (state: StateType): WindowMembershipPlans =>
  state.membershipPlans;
export const getSubscriptionProducts = (
  state: StateType,
): WindowSubscriptionProducts => state.subscriptionProducts;
export const getWordpressRoles = (state: StateType): WindowEditableRoles =>
  state.wordpressRoles;
export const getProductCategories = (
  state: StateType,
): WindowProductCategories => state.productCategories;
export const getNewslettersList = (state: StateType): WindowNewslettersList =>
  state.newslettersList;
export const canUseWooSubscriptions = (state: StateType): boolean =>
  state.canUseWooSubscriptions;
export const getWooCommerceCurrencySymbol = (state: StateType): string =>
  state.wooCurrencySymbol;
export const getWooCommerceCountries = (
  state: StateType,
): WindowWooCommerceCountries => state.wooCountries;
export const getCustomFieldsList = (state: StateType): WindowCustomFields =>
  state.customFieldsList;
export const getSegment = (state: StateType): Segment => state.segment;
export const getStaticSegmentsList = (state: StateType): StaticSegment[] =>
  state.staticSegmentsList;
export const getSubscriberCount = (state: StateType): SubscriberCount =>
  state.subscriberCount;
export const getTags = (state: StateType): Tag[] => state.tags;
export const getSignupForms = (state: StateType): SignupForm[] =>
  state.signupForms;
export const getPaymentMethods = (state: StateType): WooPaymentMethod[] =>
  state.wooPaymentMethods;
export const getShippingMethods = (state: StateType): WooShippingMethod[] =>
  state.wooShippingMethods;
export const getSegmentFilter = (
  state: StateType,
  index: number,
): AnyFormItem | undefined => {
  let found: AnyFormItem | undefined;
  if (!Array.isArray(state.segment.filters)) {
    return found;
  }

  found = { ...state.segment.filters[index] };
  return found;
};
export const getErrors = (state: StateType): string[] => state.errors;
export const getAvailableFilters = (state: StateType): GroupFilterValue[] =>
  state.allAvailableFilters;
export const findFiltersValueForSegment = (
  state: StateType,
  itemSearch: Segment,
): FilterRow[] => {
  const found: FilterRow[] = [];

  itemSearch.filters.forEach((formItem: AnyFormItem, index) => {
    state.allAvailableFilters.forEach((filter: GroupFilterValue) => {
      filter.options.forEach((option: FilterValue) => {
        if (
          option.group === formItem.segmentType &&
          option.value === formItem.action
        ) {
          found.push({
            filterValue: option,
            index,
          });
        }
      });
    });
  });
  return found;
};

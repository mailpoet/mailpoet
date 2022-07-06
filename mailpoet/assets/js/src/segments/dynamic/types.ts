export enum SegmentTypes {
  Email = 'email',
  WordPressRole = 'userRole',
  SubscribedDate = 'subscribedDate',
  WooCommerce = 'woocommerce',
  WooCommerceMembership = 'woocommerceMembership',
  WooCommerceSubscription = 'woocommerceSubscription',
}

export enum EmailActionTypes {
  OPENS_ABSOLUTE_COUNT = 'opensAbsoluteCount',
  MACHINE_OPENS_ABSOLUTE_COUNT = 'machineOpensAbsoluteCount',
  OPENED = 'opened',
  MACHINE_OPENED = 'machineOpened',
  CLICKED = 'clicked',
  CLICKED_ANY = 'clickedAny',
}

export enum SubscriberActionTypes {
  MAILPOET_CUSTOM_FIELD = 'mailpoetCustomField',
  WORDPRESS_ROLE = 'wordpressRole',
  SUBSCRIBED_DATE = 'subscribedDate',
  SUBSCRIBER_SCORE = 'subscriberScore',
  SUBSCRIBED_TO_LIST = 'subscribedToList',
  SUBSCRIBER_TAG = 'subscriberTag',
}

export enum SegmentConnectTypes {
  AND = 'and',
  OR = 'or',
}

export enum AnyValueTypes {
  ANY = 'any',
  ALL = 'all',
  NONE = 'none',
}

export type GroupFilterValue = {
  label: string;
  options: FilterValue[];
};

export interface SelectOption {
  value: string;
  label: string;
}

export interface FilterValue extends SelectOption {
  group: SegmentTypes;
}

export interface FilterRow {
  filterValue: FilterValue;
  index: number;
}

export interface FormItem {
  id?: number;
  segmentType?: string;
  action?: string;
}

export interface WordpressRoleFormItem extends FormItem {
  wordpressRole?: string[];
  operator?: string;
  value?: string;
  custom_field_id?: string;
  custom_field_type?: string;
  date_type?: string;
  segments?: number[];
  tags?: number[];
}

export interface WooCommerceFormItem extends FormItem {
  category_ids?: string[];
  product_ids?: string[];
  operator?: string;
  number_of_orders_type?: string;
  number_of_orders_count?: number;
  number_of_orders_days?: number;
  total_spent_type?: string;
  total_spent_amount?: number;
  total_spent_days?: number;
  country_code?: string[];
}

export interface WooCommerceMembershipFormItem extends FormItem {
  plan_ids?: string[];
  operator?: AnyValueTypes;
}

export interface WooCommerceSubscriptionFormItem extends FormItem {
  product_ids?: string[];
  operator?: AnyValueTypes;
}

export interface EmailFormItem extends FormItem {
  newsletter_id?: string;
  newsletters?: number[];
  link_ids?: string[];
  operator?: string;
  opens?: string;
  days?: string;
}

export type Segment = {
  id?: number;
  name?: string;
  description?: string;
  filters_connect?: SegmentConnectTypes;
  filters?: AnyFormItem[];
};

export type AnyFormItem =
  | WordpressRoleFormItem
  | WooCommerceFormItem
  | WooCommerceSubscriptionFormItem
  | EmailFormItem;

export interface SubscriberCount {
  count?: number;
  loading?: boolean;
  errors?: string[];
}

export type OnFilterChange = (value: AnyFormItem, filterIndex: number) => void;

export type WindowEditableRoles = {
  role_id: string;
  role_name: string;
}[];

export type WindowProducts = {
  id: string;
  name: string;
}[];

export type WindowMembershipPlans = {
  id: string;
  name: string;
}[];

export type WindowSubscriptionProducts = {
  id: string;
  name: string;
}[];

export type WindowProductCategories = {
  id: string;
  name: string;
}[];

export type WindowNewslettersList = {
  sent_at: string;
  subject: string;
  id: string;
}[];

export type WindowWooCommerceCountries = {
  code: string;
  name: string;
}[];

export type WindowCustomFields = {
  created_at: string;
  id: number;
  name: string;
  type: string;
  params: Record<string, unknown>;
  updated_at: string;
}[];

export type StaticSegment = {
  id: number;
  name: string;
  type: string;
  description: string;
};

export interface SegmentFormDataWindow extends Window {
  wordpress_editable_roles_list: WindowEditableRoles;
  mailpoet_products: WindowProducts;
  mailpoet_membership_plans: WindowMembershipPlans;
  mailpoet_subscription_products: WindowSubscriptionProducts;
  mailpoet_product_categories: WindowProductCategories;
  mailpoet_woocommerce_countries: WindowWooCommerceCountries;
  mailpoet_newsletters_list: WindowNewslettersList;
  mailpoet_custom_fields: WindowCustomFields;
  mailpoet_can_use_woocommerce_memberships: boolean;
  mailpoet_can_use_woocommerce_subscriptions: boolean;
  mailpoet_woocommerce_currency_symbol: string;
  mailpoet_static_segments_list: StaticSegment[];
  mailpoet_tags: Tag[];
}

export interface StateType {
  products: WindowProducts;
  membershipPlans: WindowMembershipPlans;
  subscriptionProducts: WindowSubscriptionProducts;
  wordpressRoles: WindowEditableRoles;
  productCategories: WindowProductCategories;
  newslettersList: WindowNewslettersList;
  canUseWooMemberships: boolean;
  canUseWooSubscriptions: boolean;
  wooCurrencySymbol: string;
  wooCountries: WindowWooCommerceCountries;
  customFieldsList: WindowCustomFields;
  segment: Segment;
  subscriberCount: SubscriberCount;
  errors: string[];
  allAvailableFilters: GroupFilterValue[];
  staticSegmentsList: StaticSegment[];
  tags: Tag[];
}

export enum Actions {
  SET_SEGMENT = 'SET_SEGMENT',
  SET_ERRORS = 'SET_ERRORS',
  UPDATE_SEGMENT = 'UPDATE_SEGMENT',
  UPDATE_SEGMENT_FILTER = 'UPDATE_SEGMENT_FILTER',
  UPDATE_SUBSCRIBER_COUNT = 'UPDATE_SUBSCRIBER_COUNT',
}

export interface ActionType {
  type: Actions;
}

export interface SetSegmentActionType extends ActionType {
  segment: AnyFormItem;
}

export interface SetSegmentFilerActionType extends ActionType {
  filter: AnyFormItem;
  filterIndex: number;
}

export interface SetSubscriberCountActionType extends ActionType {
  subscriberCount: SubscriberCount;
}

export interface SetErrorsActionType extends ActionType {
  errors: string[];
}

export type Tag = {
  id: number;
  name: string;
};

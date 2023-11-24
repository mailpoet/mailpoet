export enum SegmentTypes {
  Automations = 'automations',
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
  WAS_SENT = 'wasSent',
  CLICKED = 'clicked',
  CLICKED_ANY = 'clickedAny',
  NUMBER_RECEIVED = 'numberReceived',
  NUMBER_OF_CLICKS = 'numberOfClicks',
}

export enum SubscriberActionTypes {
  MAILPOET_CUSTOM_FIELD = 'mailpoetCustomField',
  WORDPRESS_ROLE = 'wordpressRole',
  SUBSCRIBED_DATE = 'subscribedDate',
  SUBSCRIBER_SCORE = 'subscriberScore',
  SUBSCRIBED_TO_LIST = 'subscribedToList',
  SUBSCRIBER_FIRST_NAME = 'subscriberFirstName',
  SUBSCRIBER_LAST_NAME = 'subscriberLastName',
  SUBSCRIBER_EMAIL = 'subscriberEmail',
  SUBSCRIBER_LAST_CLICK_DATE = 'lastClickDate',
  SUBSCRIBER_LAST_ENGAGEMENT_DATE = 'lastEngagementDate',
  SUBSCRIBER_LAST_PURCHASE_DATE = 'lastPurchaseDate',
  SUBSCRIBER_LAST_OPEN_DATE = 'lastOpenDate',
  SUBSCRIBER_LAST_PAGE_VIEW_DATE = 'lastPageViewDate',
  SUBSCRIBER_LAST_SENDING_DATE = 'lastSendingDate',
  SUBSCRIBER_TAG = 'subscriberTag',
  SUBSCRIBED_VIA_FORM = 'subscribedViaForm',
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

export enum BlankOptions {
  BLANK = 'is_blank',
  NOT_BLANK = 'is_not_blank',
}

export function isBlankOption(value: unknown): value is BlankOptions {
  return Object.values(BlankOptions).includes(value as BlankOptions);
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

export interface DateFormItem extends FormItem {
  operator?: string;
  value?: string;
}

export interface DaysPeriodItem extends FormItem {
  days?: string;
  timeframe?: Timeframe;
}

export enum Timeframe {
  ALL_TIME = 'allTime',
  IN_THE_LAST = 'inTheLast',
}

export interface TextFormItem extends FormItem {
  operator?: string;
  value?: string;
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
  form_ids?: string[];
}

export enum ReviewRating {
  ANY = 'any',
  ONE = '1',
  TWO = '2',
  THREE = '3',
  FOUR = '4',
  FIVE = '5',
}

export enum CountType {
  EQUALS = '=',
  NOT_EQUALS = '!=',
  MORE_THAN = '>',
  LESS_THAN = '<',
}

export interface WooCommerceFormItem extends FormItem {
  category_ids?: string[];
  product_ids?: string[];
  operator?: string;
  number_of_orders_type?: string;
  number_of_orders_count?: number;
  total_spent_type?: string;
  total_spent_amount?: number;
  country_code?: string[];
  single_order_value_type?: string;
  single_order_value_amount?: number;
  average_spent_type?: string;
  average_spent_amount?: string;
  payment_methods?: string[];
  shipping_methods?: string[];
  rating?: ReviewRating;
  count_type?: CountType;
  count?: string;
  days?: string;
  coupon_code_ids?: string[];
  attribute_type?: 'local' | 'taxonomy';
  attribute_taxonomy_slug?: string;
  attribute_term_ids?: string[];
  attribute_local_name?: string;
  attribute_local_values?: string[];
  tag_ids?: string[];
}

export interface AutomationsFormItem extends FormItem {
  operator?: string;
  automation_ids?: string[];
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
  emails?: string;
  clicks?: string;
}

export type DynamicSegment = {
  id: number;
  name: string;
  description?: string;
  status?: string;
  stats: string;
  count_all: string;
  count_subscribed: string;
  created_at: string;
  deleted_at: string;
  subscribers_url: string;
};

export type Segment = {
  id?: number;
  name?: string;
  description?: string;
  filters_connect?: SegmentConnectTypes;
  filters?: AnyFormItem[];
  force_creation?: boolean;
};

export type AnyFormItem =
  | AutomationsFormItem
  | DateFormItem
  | WordpressRoleFormItem
  | WooCommerceFormItem
  | WooCommerceSubscriptionFormItem
  | WooCommerceMembershipFormItem
  | EmailFormItem
  | DaysPeriodItem;

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

export type Term = {
  term_id: string;
  name: string;
  slug: string;
  taxonomy: string;
};

export type WindowProductAttributes = {
  [key: string]: {
    id: string;
    label: string;
    taxonomy: string;
    terms: Term[];
  };
};

export type WindowLocalProductAttributes = {
  [key: string]: {
    name: string;
    values: string[];
  };
};

export type WindowProductCategories = {
  id: string;
  name: string;
}[];

export type WindowProductTags = {
  id: string;
  name: string;
}[];

export type WindowNewslettersList = {
  sent_at: string;
  subject: string;
  name: string;
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
  mailpoet_product_attributes: WindowProductAttributes;
  mailpoet_local_product_attributes: WindowLocalProductAttributes;
  mailpoet_product_categories: WindowProductCategories;
  mailpoet_product_tags: WindowProductTags;
  mailpoet_woocommerce_countries: WindowWooCommerceCountries;
  mailpoet_woocommerce_payment_methods: WooPaymentMethod[];
  mailpoet_woocommerce_shipping_methods: WooShippingMethod[];
  mailpoet_newsletters_list: WindowNewslettersList;
  mailpoet_custom_fields: WindowCustomFields;
  mailpoet_can_use_woocommerce_memberships: boolean;
  mailpoet_can_use_woocommerce_subscriptions: boolean;
  mailpoet_woocommerce_currency_symbol: string;
  mailpoet_static_segments_list: StaticSegment[];
  mailpoet_tags: Tag[];
  mailpoet_signup_forms: SignupForm[];
  mailpoet_automations: Automation[];
}

export type DynamicSegmentQuery = {
  offset: number;
  limit: number;
  search: string;
  sort_by: string;
  sort_order: string;
  group: string;
};

export type DynamicSegmentGroup = {
  name: string;
  label: string;
  count: number;
};

export interface StateType {
  products: WindowProducts;
  membershipPlans: WindowMembershipPlans;
  subscriptionProducts: WindowSubscriptionProducts;
  wordpressRoles: WindowEditableRoles;
  productAttributes: WindowProductAttributes;
  localProductAttributes: WindowLocalProductAttributes;
  productCategories: WindowProductCategories;
  productTags: WindowProductTags;
  newslettersList: WindowNewslettersList;
  canUseWooMemberships: boolean;
  canUseWooSubscriptions: boolean;
  wooCurrencySymbol: string;
  wooCountries: WindowWooCommerceCountries;
  wooPaymentMethods: WooPaymentMethod[];
  wooShippingMethods: WooShippingMethod[];
  customFieldsList: WindowCustomFields;
  segment: Segment;
  subscriberCount: SubscriberCount;
  errors: string[];
  allAvailableFilters: GroupFilterValue[];
  staticSegmentsList: StaticSegment[];
  tags: Tag[];
  signupForms: SignupForm[];
  automations: Automation[];
  previousPage: string;
  dynamicSegmentsQuery: DynamicSegmentQuery | null;
  dynamicSegments: DynamicSegmentsList;
}

export type DynamicSegmentsList = {
  data: DynamicSegment[] | null;
  meta: {
    all: number;
    groups: DynamicSegmentGroup[];
  };
};

export enum Actions {
  SET_DYNAMIC_SEGMENTS = 'SET_DYNAMIC_SEGMENTS',
  SET_SEGMENT = 'SET_SEGMENT',
  UPDATE_DYNAMIC_SEGMENTS_QUERY = 'UPDATE_DYNAMIC_SEGMENTS_QUERY',
  SET_ERRORS = 'SET_ERRORS',
  SET_PREVIOUS_PAGE = 'SET_PREVIOUS_PAGE',
  RESET_SEGMENT_AND_ERRORS = 'RESET_SEGMENT_AND_ERRORS',
  UPDATE_SEGMENT = 'UPDATE_SEGMENT',
  UPDATE_SEGMENT_FILTER = 'UPDATE_SEGMENT_FILTER',
  UPDATE_SUBSCRIBER_COUNT = 'UPDATE_SUBSCRIBER_COUNT',
}

export interface ActionType {
  type: Actions;
}

export type UpdateSegmentActionData =
  | AnyFormItem
  | { name: string }
  | { description: string }
  | { filters: AnyFormItem[] }
  | { filters_connect: SegmentConnectTypes };

export interface SetDynamicSegmentsActionType extends ActionType {
  dynamicSegments: DynamicSegmentsList;
}

export interface UpdateDynamicSegmentsQueryActionType extends ActionType {
  query: DynamicSegmentQuery;
}

export interface SetSegmentActionType extends ActionType {
  segment: UpdateSegmentActionData;
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

export interface SetPreviousPageActionType extends ActionType {
  previousPage: string;
}

export type FilterProps = {
  filterIndex: number;
};

export type Tag = {
  id: number;
  name: string;
};

export type SignupForm = {
  id: string;
  name: string;
};

export type WooPaymentMethod = {
  id: string;
  name: string;
};

export type Automation = {
  id: string;
  name: string;
};

export type WooShippingMethod = {
  instanceId: string;
  name: string;
};

export type Coupon = {
  id: string;
  text: string;
};

export enum SegmentTemplateCategories {
  ENGAGEMENT = 'engagement',
  PURCHASE_HISTORY = 'purchase-history',
  SHOPPING_BEHAVIOR = 'shopping-behavior',
}

export type SegmentTemplate = {
  name: string;
  slug: string;
  description: string;
  category: SegmentTemplateCategories;
  isEssential: boolean;
  filters: AnyFormItem[];
  filtersConnect?: SegmentConnectTypes;
};

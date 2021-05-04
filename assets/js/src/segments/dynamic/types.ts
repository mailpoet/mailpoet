export enum SegmentTypes {
  Email = 'email',
  WordPressRole = 'userRole',
  SubscribedDate = 'subscribedDate',
  WooCommerce = 'woocommerce',
  WooCommerceSubscription = 'woocommerceSubscription'
}

export enum EmailActionTypes {
  OPENS_ABSOLUTE_COUNT = 'opensAbsoluteCount',
  OPENED = 'opened',
  NOT_OPENED = 'notOpened',
  CLICKED = 'clicked',
  CLICKED_ANY = 'clickedAny',
  NOT_CLICKED = 'notClicked',
}

export enum SubscriberActionTypes {
  WORDPRESS_ROLE = 'wordpressRole',
  SUBSCRIBED_DATE = 'subscribedDate',
}

export interface SelectOption {
  value: string;
  label: string;
}

export interface FilterValue extends SelectOption {
  group: SegmentTypes;
}

export interface FormItem {
  segmentType?: string;
  name?: string;
  description?: string;
  action?: string;
}

export interface WordpressRoleFormItem extends FormItem {
  wordpressRole?: string;
  operator?: string;
  value?: string;
}

export interface WooCommerceFormItem extends FormItem {
  category_id?: string;
  product_id?: string;
  number_of_orders_type?: string;
  number_of_orders_count?: number;
  number_of_orders_days?: number;
  total_spent_type?: string;
  total_spent_amount?: number;
  total_spent_days?: number;
  country_code?: string;
}

export interface WooCommerceSubscriptionFormItem extends FormItem {
  product_id?: string;
}

export interface EmailFormItem extends FormItem {
  newsletter_id?: string;
  link_id?: string;
  operator?: string;
  opens?: string;
  days?: string;
}

export type AnyFormItem =
  WordpressRoleFormItem |
  WooCommerceFormItem |
  WooCommerceSubscriptionFormItem |
  EmailFormItem;

export type OnFilterChange = (value: AnyFormItem) => void;

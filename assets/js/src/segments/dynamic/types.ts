export enum SegmentTypes {
  Email = 'email',
  WordPressRole = 'userRole',
  WooCommerce = 'woocommerce',
}

export enum EmailActionTypes {
  OPENS_ABSOLUTE_COUNT = 'opensAbsoluteCount',
  OPENED = 'opened',
  NOT_OPENED = 'notOpened',
  CLICKED = 'clicked',
  NOT_CLICKED = 'notClicked',
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
}

export interface WordpressRoleFormItem extends FormItem {
  wordpressRole?: string;
}

export interface WooCommerceFormItem extends FormItem {
  action?: string;
  category_id?: string;
  product_id?: string;
}

export interface EmailFormItem extends FormItem {
  action?: string;
  newsletter_id?: string;
  link_id?: string;
}

export type AnyFormItem = WordpressRoleFormItem | WooCommerceFormItem | EmailFormItem;

export type OnFilterChange = (value: AnyFormItem) => void;

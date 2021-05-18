interface SegmentFormDataWindow extends Window {
  wordpress_editable_roles_list: {
    role_id: string;
    role_name: string;
  }[];

  mailpoet_products: {
    id: string;
    name: string;
  }[];

  mailpoet_subscription_products: {
    id: string;
    name: string;
  }[];

  mailpoet_product_categories: {
    id: string;
    name: string;
  }[];

  mailpoet_woocommerce_countries: {
    code: string;
    name: string;
  }[];

  mailpoet_newsletters_list: {
    sent_at: string;
    subject: string;
    id: string;
  }[];

  mailpoet_custom_fields: {
    created_at: string;
    id: number;
    name: string;
    type: string;
    params: Record<string, unknown>;
    updated_at: string;
  }

  mailpoet_can_use_woocommerce_subscriptions: boolean;
  mailpoet_woocommerce_currency_symbol: string;
}

declare let window: SegmentFormDataWindow;

export const SegmentFormData = {
  products: window.mailpoet_products,
  subscriptionProducts: window.mailpoet_subscription_products,
  productCategories: window.mailpoet_product_categories,
  newslettersList: window.mailpoet_newsletters_list,
  wordpressRoles: window.wordpress_editable_roles_list,
  canUseWooSubscriptions: window.mailpoet_can_use_woocommerce_subscriptions,
  wooCurrencySymbol: window.mailpoet_woocommerce_currency_symbol,
  wooCountries: window.mailpoet_woocommerce_countries,
  customFieldsList: window.mailpoet_custom_fields,
};

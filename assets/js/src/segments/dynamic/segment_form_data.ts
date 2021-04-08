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

  mailpoet_newsletters_list: {
    sent_at: string;
    subject: string;
    id: string;
  }[];
}

declare let window: SegmentFormDataWindow;

export const SegmentFormData = {
  products: window.mailpoet_products,
  subscriptionProducts: window.mailpoet_subscription_products,
  productCategories: window.mailpoet_product_categories,
  newslettersList: window.mailpoet_newsletters_list,
  wordpressRoles: window.wordpress_editable_roles_list,
};

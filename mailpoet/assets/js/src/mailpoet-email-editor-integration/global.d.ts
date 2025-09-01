interface Window {
  WooCommerceEmailEditor: {
    current_post_type: string;
    current_post_id: string;
    current_wp_user_email: string;
    editor_settings: {
      [key: string]: string | boolean | number | object;
    };
    editor_theme: string;
    user_theme_post_id: string;
    urls: {
      listings: string;
      send: string;
      back: string;
    };
  };
  mailpoet_is_automation_newsletter: boolean;
}

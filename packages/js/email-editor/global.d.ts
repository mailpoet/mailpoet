interface Window {
  MailPoetEmailEditor: {
    json_api_root: string;
    api_token: string;
    api_version: string;
    cdn_url: string;
    is_premium_plugin_active: boolean;
    current_wp_user_email: string;
    urls: {
      listings: string;
    };
    editor_settings: unknown; // Can't import type in global.d.ts. Typed in getEditorSettings() in store/settings.ts
    email_styles: unknown; // Can't import type in global.d.ts. Typed in getEmailStyles() in store/settings.ts
    editor_layout: unknown; // Can't import type in global.d.ts. Typed in getEmailLayout() in store/settings.ts
    editor_theme: unknown; // Can't import type in global.d.ts. Typed in getEditorTheme() in store/settings.ts
  };
}

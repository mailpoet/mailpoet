interface Window {
  MailPoetEmailEditor: {
    json_api_root: string;
    api_token: string;
    api_version: string;
    current_wp_user_email: string;
    editor_settings: unknown; // Can't import type in global.d.ts. Typed in getEditorSettings() in store/settings.ts
    email_layout_styles: unknown; // Can't import type in global.d.ts. Typed in getEditorSettings() in store/settings.ts
  };
}

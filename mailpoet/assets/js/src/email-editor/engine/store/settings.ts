import { EmailEditorSettings, EmailEditorLayout, EmailTheme } from './types';

export function getEditorSettings(): EmailEditorSettings {
  return window.MailPoetEmailEditor.editor_settings as EmailEditorSettings;
}

export function getEditorLayout(): EmailEditorLayout {
  return window.MailPoetEmailEditor.editor_layout as EmailEditorLayout;
}

export function getCdnUrl(): string {
  return window.MailPoetEmailEditor.cdn_url;
}

export function isPremiumPluginActive(): boolean {
  return window.MailPoetEmailEditor.is_premium_plugin_active;
}

export function getEditorTheme(): EmailTheme {
  return window.MailPoetEmailEditor.editor_theme as EmailTheme;
}

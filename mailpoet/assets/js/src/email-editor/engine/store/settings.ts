import { EmailEditorSettings, EmailEditorLayout, EmailTheme } from './types';

export function getEditorSettings(): EmailEditorSettings {
  return window.MailPoetEmailEditor.editor_settings as EmailEditorSettings;
}

export function getEditorLayout(): EmailEditorLayout {
  return window.MailPoetEmailEditor.editor_layout as EmailEditorLayout;
}

export function getEditorTheme(): EmailTheme {
  return window.MailPoetEmailEditor.editor_theme as EmailTheme;
}

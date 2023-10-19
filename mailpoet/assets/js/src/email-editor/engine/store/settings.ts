import { EmailEditorSettings, EmailLayoutStyles } from './types';

export function getEditorSettings(): EmailEditorSettings {
  return window.MailPoetEmailEditor.editor_settings as EmailEditorSettings;
}

export function getEmailLayoutStyles(): EmailLayoutStyles {
  return window.MailPoetEmailEditor.email_layout_styles as EmailLayoutStyles;
}

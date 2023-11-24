import {
  EmailEditorSettings,
  EmailLayoutStyles,
  EmailEditorLayout,
} from './types';

export function getEditorSettings(): EmailEditorSettings {
  return window.MailPoetEmailEditor.editor_settings as EmailEditorSettings;
}

export function getEmailLayoutStyles(): EmailLayoutStyles {
  return window.MailPoetEmailEditor.email_layout_styles as EmailLayoutStyles;
}

export function getEditorLayout(): EmailEditorLayout {
  return window.MailPoetEmailEditor.editor_layout as EmailEditorLayout;
}

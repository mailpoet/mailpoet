import { EmailEditorSettings } from './types';

export function getEditorSettings(): EmailEditorSettings {
  return window.MailPoetEmailEditor.editor_settings as EmailEditorSettings;
}

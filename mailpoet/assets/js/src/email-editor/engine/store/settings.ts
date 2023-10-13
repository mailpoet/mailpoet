import { SETTINGS_DEFAULTS } from '@wordpress/block-editor';
import { EmailEditorSettings } from './types';

export function getEditorSettings(): EmailEditorSettings {
  const settings = window.MailPoetEmailEditor
    .editor_settings as EmailEditorSettings;
  // eslint-disable-next-line no-underscore-dangle
  settings.__experimentalFeatures.color.palette.default =
    SETTINGS_DEFAULTS.colors;
  return settings;
}

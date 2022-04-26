import { fetchLinkSuggestions } from 'form_editor/utils/link_suggestions';
import { SETTINGS_DEFAULTS } from '@wordpress/block-editor';
import { uploadMedia } from '@wordpress/media-utils';
import { select } from '@wordpress/data';

export const getEditorSettings = () => ({
  ...SETTINGS_DEFAULTS,
  allowedMimeTypes: 'image/*',
  mediaUpload: select('core').canUser('create', 'media') ? uploadMedia : null,
  maxWidth: 580,
  enableCustomSpacing: true,
  enableCustomLineHeight: true,
  disableCustomFontSizes: false,
  enableCustomUnits: true,
  __experimentalFetchLinkSuggestions: fetchLinkSuggestions,
  __experimentalBlockPatterns: [], // we don't want patterns in our inserter
  __experimentalBlockPatternCategories: [],
  __experimentalFeatures: {
    color: {
      custom: true,
      text: true,
      background: true,
      customGradient: false,
      defaultPalette: true,
      palette: {
        default: SETTINGS_DEFAULTS.colors,
      },
      gradients: {},
    },
  },
});

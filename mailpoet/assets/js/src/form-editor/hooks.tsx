import { ComponentType } from 'react';
import { MediaUpload } from '@wordpress/media-utils';
import { addFilter } from '@wordpress/hooks';

export const initHooks = (): void => {
  // This hook replaces dummy media upload buttons within Gutenberg
  // see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/media-upload/README.md
  const replaceMediaUpload = (): ComponentType => MediaUpload;
  addFilter(
    'editor.MediaUpload',
    'mailpoet/form-editor/replace-media-upload',
    replaceMediaUpload,
  );
};

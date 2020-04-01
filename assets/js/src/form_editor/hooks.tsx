import { MediaUpload } from '@wordpress/media-utils';
import { addFilter } from '@wordpress/hooks';

export default () => {
  // This hook replaces dummy media upload buttons within Gutenberg
  // see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/media-upload/README.md
  const replaceMediaUpload = () => MediaUpload;
  addFilter(
    'editor.MediaUpload',
    'mailpoet/form-editor/replace-media-upload',
    replaceMediaUpload
  );
};

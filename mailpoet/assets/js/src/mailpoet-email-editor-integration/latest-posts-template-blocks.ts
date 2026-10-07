import type { BlockConfiguration } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';
import { TEMPLATE_BLOCK_NAME } from 'mailpoet-custom-email-editor-blocks/latest-posts/constants';

const FILTER_NAMESPACE = 'mailpoet/latest-posts-template-blocks';

// Mirrors LatestPosts::TEMPLATE_CORE_BLOCKS on the server.
export const TEMPLATE_CORE_BLOCKS = [
  'core/post-featured-image',
  'core/post-title',
  'core/post-excerpt',
  'core/post-date',
  'core/post-author',
  'core/post-author-name',
  'core/post-author-biography',
  'core/post-terms',
  'core/avatar',
  'core/read-more',
];

// These blocks only have a post to show inside the Post Template, so the
// inserter should offer them there and nowhere else.
export const limitToPostTemplate = (
  settings: BlockConfiguration,
  name: string,
): BlockConfiguration => {
  if (!TEMPLATE_CORE_BLOCKS.includes(name)) {
    return settings;
  }
  return {
    ...settings,
    ancestor: [...(settings.ancestor ?? []), TEMPLATE_BLOCK_NAME],
  };
};

export const registerLatestPostsTemplateBlocks = (): void => {
  addFilter('blocks.registerBlockType', FILTER_NAMESPACE, limitToPostTemplate);
};

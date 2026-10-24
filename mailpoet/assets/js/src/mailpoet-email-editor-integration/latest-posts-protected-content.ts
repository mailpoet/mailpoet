import type { BlockConfiguration } from '@wordpress/blocks';
import { QUERY_CONTEXT } from 'mailpoet-custom-email-editor-blocks/latest-posts/constants';
import { hidesPostContent } from 'mailpoet-custom-email-editor-blocks/latest-posts/password-protected';
import type { PasswordProtectedMode } from 'mailpoet-custom-email-editor-blocks/latest-posts/password-protected';

// Mirrors LatestPosts::PROTECTED_POST_BLOCKS on the server: core blocks that
// print the content of the post they are given.
export const PROTECTED_POST_BLOCKS = ['core/post-excerpt', 'core/post-content'];

export type ProtectedContentContext = {
  postId?: number;
  postType?: string;
  [QUERY_CONTEXT]?: { passwordProtected?: PasswordProtectedMode };
};

export type ProtectedContentBlockProps = {
  name: string;
  context?: ProtectedContentContext;
};

// A block only receives the context keys it declares, and core's post blocks
// know nothing of ours.
export const addPasswordProtectedContext = (
  settings: BlockConfiguration,
  name: string,
): BlockConfiguration => {
  if (!PROTECTED_POST_BLOCKS.includes(name)) {
    return settings;
  }
  const usesContext = settings.usesContext ?? [];
  if (usesContext.includes(QUERY_CONTEXT)) {
    return settings;
  }
  return { ...settings, usesContext: [...usesContext, QUERY_CONTEXT] };
};

/**
 * Whether the block has to keep the post's content to itself. Core's post
 * blocks show it to anyone who can edit the post, password or not, while the
 * email leaves it out, and a canvas that shows more than the email does is how
 * people conclude the setting is broken.
 *
 * Says nothing about the post itself: the caller still has to look up whether
 * this one needs a password.
 */
export const hidesProtectedContent = ({
  name,
  context,
}: ProtectedContentBlockProps): boolean => {
  if (!PROTECTED_POST_BLOCKS.includes(name)) {
    return false;
  }
  const query = context?.[QUERY_CONTEXT];
  // No query context means the block is not inside a Latest Posts template.
  if (!query) {
    return false;
  }
  return hidesPostContent(query.passwordProtected);
};

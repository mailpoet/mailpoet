import { useBlockProps } from '@wordpress/block-editor';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { addFilter } from '@wordpress/hooks';
import { isProtectedRecord } from 'mailpoet-custom-email-editor-blocks/latest-posts/password-protected';
import type { ComponentType } from 'react';
import {
  addPasswordProtectedContext,
  hidesProtectedContent,
  PROTECTED_POST_BLOCKS,
} from './latest-posts-protected-content';
import type { ProtectedContentBlockProps } from './latest-posts-protected-content';

const FILTER_NAMESPACE = 'mailpoet/latest-posts-protected-content';

/**
 * Takes the place of the block's own output, so the canvas shows what the email
 * will send: nothing where the post's content would be.
 */
function HiddenProtectedContent(): JSX.Element {
  return <div {...useBlockProps()} />;
}

/**
 * Looks the post up to see whether it needs a password. Its own component, so
 * only the blocks that print post content subscribe to the store: the filter
 * below wraps every block in the editor.
 */
function ProtectedContentGate({
  BlockEdit,
  props,
}: {
  BlockEdit: ComponentType<ProtectedContentBlockProps>;
  props: ProtectedContentBlockProps;
}): JSX.Element {
  const { postId, postType } = props.context ?? {};
  const hides = hidesProtectedContent(props);

  const isProtectedPost = useSelect(
    (select) => {
      if (!hides || !postId || !postType) {
        return false;
      }
      return isProtectedRecord(
        select(coreStore).getEntityRecord('postType', postType, postId) as {
          content?: { protected?: boolean };
        } | null,
      );
    },
    [hides, postId, postType],
  );

  return isProtectedPost ? (
    <HiddenProtectedContent />
  ) : (
    <BlockEdit {...props} />
  );
}

const withProtectedContentHidden = createHigherOrderComponent(
  (BlockEdit: ComponentType<ProtectedContentBlockProps>) =>
    function MailPoetLatestPostsProtectedContent(
      props: ProtectedContentBlockProps,
    ): JSX.Element {
      // The block's name never changes, so the gate below keeps its hooks for
      // as long as the block lives.
      if (!PROTECTED_POST_BLOCKS.includes(props.name)) {
        return <BlockEdit {...props} />;
      }
      return <ProtectedContentGate BlockEdit={BlockEdit} props={props} />;
    },
  'withProtectedContentHidden',
);

export const registerLatestPostsProtectedContent = (): void => {
  addFilter(
    'blocks.registerBlockType',
    FILTER_NAMESPACE,
    addPasswordProtectedContext,
  );
  addFilter('editor.BlockEdit', FILTER_NAMESPACE, withProtectedContentHidden);
};

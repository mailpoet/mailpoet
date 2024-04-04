/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';

import {
  ErrorBoundary,
  PostLockedModal,
  // @ts-expect-error No types for this exist yet.
  privateApis as editorPrivateApis,
} from '@wordpress/editor';
import { useMemo } from '@wordpress/element';
import { SlotFillProvider } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { storeName } from '../../store';

/**
 * Internal dependencies
 */
import { Layout } from './layout';
import { unlock } from '../../../lock-unlock';
import useNavigateToEntityRecord from '../../hooks/use-navigate-to-entity-record';

const { ExperimentalEditorProvider } = unlock(editorPrivateApis);

function InnerEditor({
  postId: initialPostId,
  postType: initialPostType,
  settings,
  initialEdits,
  ...props
}) {
  const {
    currentPost,
    onNavigateToEntityRecord,
    onNavigateToPreviousEntityRecord,
    // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
  } = useNavigateToEntityRecord(initialPostId, initialPostType, 'post-only');

  const { post, template } = useSelect(
    (select) => {
      const { getEntityRecord } = select(coreStore);
      const { getEditedPostTemplate } = select(storeName);

      const postObject = getEntityRecord(
        'postType',
        // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
        currentPost.postType,
        // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
        currentPost.postId,
      );
      return {
        template: getEditedPostTemplate(),
        post: postObject,
      };
    },
    [currentPost.postType, currentPost.postId],
  );

  const editorSettings = useMemo(
    // eslint-disable-next-line @typescript-eslint/no-unsafe-return
    () => ({
      ...settings,
      onNavigateToEntityRecord,
      onNavigateToPreviousEntityRecord,
      defaultRenderingMode: 'post-only',
    }),
    [settings, onNavigateToEntityRecord, onNavigateToPreviousEntityRecord],
  );

  if (!post) {
    return null;
  }

  return (
    <SlotFillProvider>
      <ExperimentalEditorProvider
        settings={editorSettings}
        post={post}
        initialEdits={initialEdits}
        useSubRegistry={false}
        __unstableTemplate={template}
        {...props}
      >
        {/* @ts-expect-error Tada */}
        <ErrorBoundary>
          <Layout />
        </ErrorBoundary>
        <PostLockedModal />
      </ExperimentalEditorProvider>
    </SlotFillProvider>
  );
}

export default InnerEditor;

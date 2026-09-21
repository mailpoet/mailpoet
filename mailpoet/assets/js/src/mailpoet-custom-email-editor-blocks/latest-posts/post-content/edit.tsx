import { __, sprintf } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useBlockProps } from '@wordpress/block-editor';
import { Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { sanitizePreviewHtml } from './sanitize-preview-html';

type EditProps = {
  context: {
    postId?: number;
    postType?: string;
  };
};

type ContentRecord = { content?: { rendered?: string } };

export function Edit({ context }: EditProps): JSX.Element {
  const { postId, postType } = context;
  const blockProps = useBlockProps({
    className: 'mailpoet-latest-posts__post-content',
  });

  const { record, hasResolved } = useSelect(
    (select) => {
      if (!postId || !postType) {
        return { record: null, hasResolved: false };
      }
      return {
        record: select(coreStore).getEntityRecord(
          'postType',
          postType,
          postId,
        ) as ContentRecord | null,
        hasResolved: select(coreStore).hasFinishedResolution(
          'getEntityRecord',
          ['postType', postType, postId],
        ),
      };
    },
    [postId, postType],
  );

  if (!postId || !postType) {
    return (
      <div {...blockProps}>
        <p>{__('Displays the content of each post.', 'mailpoet')}</p>
      </div>
    );
  }

  if (!hasResolved) {
    return (
      <div {...blockProps}>
        <Spinner />
      </div>
    );
  }

  if (!record) {
    return (
      <div {...blockProps}>
        <p>
          {sprintf(
            // translators: %d is the post ID.
            __('Could not load the post (ID: %d). Was it deleted?', 'mailpoet'),
            postId,
          )}
        </p>
      </div>
    );
  }

  return (
    <div
      {...blockProps}
      // Read-only preview of the post's rendered content, reduced to the same
      // markup the email itself carries before it goes into the canvas.
      // eslint-disable-next-line react/no-danger
      dangerouslySetInnerHTML={{
        __html: sanitizePreviewHtml(record.content?.rendered ?? ''),
      }}
    />
  );
}

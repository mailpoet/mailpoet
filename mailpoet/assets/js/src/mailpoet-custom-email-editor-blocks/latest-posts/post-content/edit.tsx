import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useBlockProps } from '@wordpress/block-editor';
import { Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';

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

  const record = useSelect(
    (select) => {
      if (!postId || !postType) {
        return null;
      }
      return select(coreStore).getEntityRecord(
        'postType',
        postType,
        postId,
      ) as ContentRecord | null;
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

  if (!record) {
    return (
      <div {...blockProps}>
        <Spinner />
      </div>
    );
  }

  return (
    <div
      {...blockProps}
      // Read-only preview using the rendered content from the site's own REST API.
      // eslint-disable-next-line react/no-danger
      dangerouslySetInnerHTML={{ __html: record.content?.rendered ?? '' }}
    />
  );
}

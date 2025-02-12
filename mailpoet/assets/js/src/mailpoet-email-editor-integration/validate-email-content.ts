import { useMemo } from '@wordpress/element';
import { createBlock } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { dispatch, useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as coreDataStore } from '@wordpress/core-data';

const emailEditorStore = 'email-editor/editor';

const contentLink = `<a data-link-href='[mailpoet/subscription-unsubscribe-url]' contenteditable='false' style='text-decoration: underline;' class='mailpoet-email-editor__personalization-tags-link'>${__(
  'Unsubscribe',
  'mailpoet',
)}</a> | <a data-link-href='[mailpoet/subscription-manage-url]' contenteditable='false' style='text-decoration: underline;' class='mailpoet-email-editor__personalization-tags-link'>${__(
  'Manage subscription',
  'mailpoet',
)}</a>`;

export function useValidationRules() {
  const { contentBlockId, hasFooter } = useSelect((select) => {
    const allBlocks = select(blockEditorStore).getBlocks();
    const noBodyBlocks = allBlocks.filter(
      (block) =>
        block.name !== 'mailpoet/powered-by-mailpoet' &&
        block.name !== 'core/post-content',
    );
    // @ts-expect-error getBlocksByName is not defined in types
    const blocks = select(blockEditorStore).getBlocksByName(
      'core/post-content',
    ) as string[] | undefined;
    return {
      contentBlockId: blocks?.[0],
      hasFooter: noBodyBlocks.length > 0,
    };
  });

  /* eslint-disable @typescript-eslint/ban-ts-comment */
  const { editedTemplateContent, postTemplateId } = useSelect((mapSelect) => ({
    editedTemplateContent:
      // @ts-ignore
      mapSelect(emailEditorStore).getCurrentTemplateContent() as string,
    postTemplateId:
      // @ts-ignore
      mapSelect(emailEditorStore).getCurrentTemplate()?.id as string,
  }));

  return useMemo(() => {
    const linksParagraphBlock = createBlock('core/paragraph', {
      align: 'center',
      fontSize: 'small',
      content: contentLink,
    });

    return [
      {
        id: 'missing-unsubscribe-link',
        test: (emailContent: string) =>
          !emailContent.includes('[mailpoet/subscription-unsubscribe-url]'),
        message: __(
          'All emails must include an "Unsubscribe" link.',
          'mailpoet',
        ),
        actions: [
          {
            label: __('Insert link', 'mailpoet'),
            onClick: () => {
              if (!hasFooter) {
                // update the email content
                void dispatch(blockEditorStore).insertBlock(
                  linksParagraphBlock,
                  undefined,
                  contentBlockId,
                );
              } else {
                // update the template
                void dispatch(coreDataStore).editEntityRecord(
                  'postType',
                  'wp_template',
                  postTemplateId,
                  {
                    content: `
                      ${editedTemplateContent}
                      <!-- wp:paragraph {"align":"center","fontSize":"small"} -->
                      ${contentLink}
                      <!-- /wp:paragraph -->
                    `,
                  },
                );
              }
            },
          },
        ],
      },
    ];
  }, [contentBlockId, postTemplateId, hasFooter, editedTemplateContent]);
}

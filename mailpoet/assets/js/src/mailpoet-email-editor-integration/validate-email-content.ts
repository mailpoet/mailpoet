import { createBlock } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { dispatch, select } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as coreDataStore } from '@wordpress/core-data';
import { EmailContentValidationRule } from '@woocommerce/email-editor/build-types/store';

const emailEditorStore = 'email-editor/editor';

const contentLink = `<a data-link-href='[mailpoet/subscription-unsubscribe-url]' contenteditable='false' style='text-decoration: underline;' class='mailpoet-email-editor__personalization-tags-link'>${__(
  'Unsubscribe',
  'mailpoet',
)}</a> | <a data-link-href='[mailpoet/subscription-manage-url]' contenteditable='false' style='text-decoration: underline;' class='mailpoet-email-editor__personalization-tags-link'>${__(
  'Manage subscription',
  'mailpoet',
)}</a>`;

function getEditorContext() {
  const allBlocks = select(blockEditorStore).getBlocks();
  const noBodyBlocks = allBlocks.filter(
    (block) =>
      block.name !== 'mailpoet/powered-by-mailpoet' &&
      block.name !== 'core/post-content',
  );

  // @ts-expect-error getBlocksByName is not typed
  const blocks = select(blockEditorStore).getBlocksByName(
    'core/post-content',
  ) as string[] | undefined;

  const editedTemplateContent = select(
    emailEditorStore,
    // @ts-expect-error getCurrentTemplateContent is not typed
  ).getCurrentTemplateContent() as string;

  // @ts-expect-error getCurrentTemplate is not typed
  const postTemplateId = select(emailEditorStore).getCurrentTemplate()
    ?.id as string;

  return {
    contentBlockId: blocks?.[0],
    hasFooter: noBodyBlocks.length > 0,
    editedTemplateContent,
    postTemplateId,
  };
}

export const emailValidationRule: EmailContentValidationRule = {
  id: 'missing-unsubscribe-link',
  testContent: (emailContent: string) =>
    !emailContent.includes('[mailpoet/subscription-unsubscribe-url]'),
  message: __('All emails must include an "Unsubscribe" link.', 'mailpoet'),
  actions: [
    {
      label: __('Insert link', 'mailpoet'),
      onClick: () => {
        const {
          contentBlockId,
          hasFooter,
          editedTemplateContent,
          postTemplateId,
        } = getEditorContext();

        const linksParagraphBlock = createBlock('core/paragraph', {
          align: 'center',
          fontSize: 'small',
          content: contentLink,
        });

        if (!hasFooter) {
          void dispatch(blockEditorStore).insertBlock(
            linksParagraphBlock,
            undefined,
            contentBlockId,
          );
        } else {
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
};

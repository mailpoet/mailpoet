import { createBlock } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { dispatch, select } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';
import { EmailContentValidationRule } from '@woocommerce/email-editor/build-types/store';

const contentLink = `<a data-link-href="[mailpoet/subscription-unsubscribe-url]" contenteditable="false" style="text-decoration: underline;" class="mailpoet-email-editor__personalization-tags-link">${__(
  'Unsubscribe',
  'mailpoet',
)}</a> | <a data-link-href="[mailpoet/subscription-manage-url]" contenteditable="false" style="text-decoration: underline;" class="mailpoet-email-editor__personalization-tags-link">${__(
  'Manage subscription',
  'mailpoet',
)}</a>`;

export const emailValidationRule: EmailContentValidationRule = {
  id: 'missing-unsubscribe-link',
  testContent: (emailContent: string) =>
    !emailContent.includes('[mailpoet/subscription-unsubscribe-url]'),
  message: __('All emails must include an "Unsubscribe" link.', 'mailpoet'),
  actions: [
    {
      label: __('Insert link', 'mailpoet'),
      onClick: () => {
        const linksParagraphBlock = createBlock('core/paragraph', {
          align: 'center',
          fontSize: 'small',
          content: contentLink,
        });

        const currentPostType = select(editorStore).getCurrentPostType();
        const isEditingTemplate = currentPostType === 'wp_template';

        if (isEditingTemplate) {
          // TEMPLATE MODE: Insert into the template's root blocks
          const templateBlocks = select(blockEditorStore).getBlocks();
          void dispatch(blockEditorStore).insertBlock(
            linksParagraphBlock,
            templateBlocks.length,
            '',
          );
        } else {
          // POST MODE: Insert into the email post content
          const postBlocks = select(editorStore).getEditorBlocks();
          const newBlocks = [...postBlocks, linksParagraphBlock];
          void dispatch(editorStore).resetEditorBlocks(newBlocks);
        }
      },
    },
  ],
};

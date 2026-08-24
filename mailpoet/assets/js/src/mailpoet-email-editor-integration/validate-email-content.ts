import { createBlock } from '@wordpress/blocks';
import { __, _x } from '@wordpress/i18n';
import { dispatch, select } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';
import type { EmailContentValidationRule } from '@woocommerce/email-editor/build-types/store';

const contentLink = `<a data-link-href="[mailpoet/subscription-unsubscribe-url]" contenteditable="false" class="mailpoet-email-editor__personalization-tags-link">${__(
  'Unsubscribe',
  'mailpoet',
)}</a> | <a data-link-href="[mailpoet/subscription-manage-url]" contenteditable="false" class="mailpoet-email-editor__personalization-tags-link">${__(
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

const TRACKING_OPT_OUT_TAG = '[mailpoet/subscription-tracking-opt-out-url]';
// A merchant may have pasted the legacy shortcode into a custom block instead of
// using the personalization tag. Both become the same link at send time, via
// LinksToShortcodesConvertor, so accept either and do not nag about it.
const TRACKING_OPT_OUT_SHORTCODE = '[link:subscription_tracking_opt_out_url]';

const trackingOptOutLink = `<a data-link-href="${TRACKING_OPT_OUT_TAG}" contenteditable="false" class="mailpoet-email-editor__personalization-tags-link">${__(
  'Opt out of tracking',
  'mailpoet',
)}</a>`;

export const trackingOptOutValidationRule: EmailContentValidationRule = {
  id: 'missing-tracking-opt-out-link',
  testContent: (emailContent: string) =>
    !emailContent.includes(TRACKING_OPT_OUT_TAG) &&
    !emailContent.includes(TRACKING_OPT_OUT_SHORTCODE),
  message: __(
    'Your tracking settings ask every subscriber for consent, so this email needs a tracking opt-out link.',
    'mailpoet',
  ),
  actions: [
    {
      label: _x(
        'Insert link',
        'Button that adds the tracking opt-out link to the email',
        'mailpoet',
      ),
      onClick: () => {
        const linkParagraphBlock = createBlock('core/paragraph', {
          align: 'center',
          fontSize: 'small',
          content: trackingOptOutLink,
        });

        const currentPostType = select(editorStore).getCurrentPostType();
        const isEditingTemplate = currentPostType === 'wp_template';

        if (isEditingTemplate) {
          // TEMPLATE MODE: Insert into the template's root blocks
          const templateBlocks = select(blockEditorStore).getBlocks();
          void dispatch(blockEditorStore).insertBlock(
            linkParagraphBlock,
            templateBlocks.length,
            '',
          );
        } else {
          // POST MODE: Insert into the email post content
          const postBlocks = select(editorStore).getEditorBlocks();
          void dispatch(editorStore).resetEditorBlocks([
            ...postBlocks,
            linkParagraphBlock,
          ]);
        }
      },
    },
  ],
};

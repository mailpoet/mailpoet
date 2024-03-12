import { __ } from '@wordpress/i18n';
import { storeName } from 'email-editor/engine/store';
import { select, subscribe, dispatch } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';

let isNoticeDisplayed = false;
const noticeId = 'missing-unsubscribe-link';

const dismissNotice = (): void => {
  dispatch('core/editor').unlockPostSaving(noticeId);
  void dispatch('core/notices').removeNotice(noticeId);
};

const insertLink = (): void => {
  void dispatch(blockEditorStore).insertBlock(
    createBlock('core/paragraph', {
      className: 'has-small-font-size',
      content: `<a href="[link:subscription_unsubscribe_url]">${__(
        'Unsubscribe',
        'mailpoet',
      )}</a> | <a href="[link:subscription_manage_url]">${__(
        'Manage subscription',
        'mailpoet',
      )}</a>`,
    }),
  );
};

const showNotice = (): void => {
  dispatch('core/editor').lockPostSaving(noticeId);
  void dispatch('core/notices').createNotice(
    'error',
    __('All emails must include an "Unsubscribe" link.', 'mailpoet'),
    {
      id: noticeId,
      isDismissible: false,
      actions: [
        {
          label: __('Insert link', 'mailpoet'),
          onClick: () => {
            insertLink();
            dismissNotice();
          },
        },
      ],
    },
  );
};

subscribe(() => {
  const hasUnsubscribeLink = select(storeName).hasUnsubscribeLink();

  if (hasUnsubscribeLink && isNoticeDisplayed) {
    isNoticeDisplayed = false;
    dismissNotice();
  }

  if (!hasUnsubscribeLink && !isNoticeDisplayed) {
    isNoticeDisplayed = true;
    showNotice();
  }
}, storeName);

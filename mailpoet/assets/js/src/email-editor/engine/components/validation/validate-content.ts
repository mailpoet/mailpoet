import { __ } from '@wordpress/i18n';
import { dispatch } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import {
  addValidationError,
  hasValidationError,
  removeValidationError,
} from './utils';

const rules = [
  {
    id: 'missing-unsubscribe-link',
    test: (content) => !content.includes('[link:subscription_unsubscribe_url]'),
    message: __('All emails must include an "Unsubscribe" link.', 'mailpoet'),
    actions: [
      {
        label: __('Insert link', 'mailpoet'),
        onClick: () => {
          removeValidationError('missing-unsubscribe-link');
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
        },
      },
    ],
  },
];

export const validateContent = (content: string): boolean => {
  let isValid = true;
  rules.forEach(({ id, test, message, actions }) => {
    if (test(content)) {
      addValidationError(id, message, actions);
      isValid = false;
    } else if (hasValidationError(id)) {
      removeValidationError(id);
    }
  });
  return isValid;
};

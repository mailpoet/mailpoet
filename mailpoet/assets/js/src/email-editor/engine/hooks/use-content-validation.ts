import { __ } from '@wordpress/i18n';
import { useCallback, useMemo } from '@wordpress/element';
import { dispatch, useSelect, subscribe } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import { useDebounce } from '@wordpress/compose';
import { storeName as emailEditorStore } from 'email-editor/engine/store';
import { useShallowEqual } from './use-shallow-equal';
import { useValidationNotices } from './use-validation-notices';

export type ContentValidationData = {
  isValid: boolean;
  validateContent: () => boolean;
};

export const useContentValidation = (): ContentValidationData => {
  const contentBlockId = useSelect((select) => {
    // @ts-expect-error getBlocksByName is not defined in types
    const blocks = select(blockEditorStore).getBlocksByName(
      'core/post-content',
    ) as string[] | undefined;
    return blocks?.[0];
  });

  const { addValidationNotice, hasValidationNotice, removeValidationNotice } =
    useValidationNotices();
  const { editedContent, editedTemplateContent } = useSelect((mapSelect) => ({
    editedContent: mapSelect(emailEditorStore).getEditedEmailContent(),
    editedTemplateContent: mapSelect(emailEditorStore).getTemplateContent(),
  }));

  const content = useShallowEqual(editedContent);
  const templateContent = useShallowEqual(editedTemplateContent);

  const rules = useMemo(
    () => [
      {
        id: 'missing-unsubscribe-link',
        test: (emailContent) =>
          !emailContent.includes('[link:subscription_unsubscribe_url]'),
        message: __(
          'All emails must include an "Unsubscribe" link.',
          'mailpoet',
        ),
        actions: [
          {
            label: __('Insert link', 'mailpoet'),
            onClick: () => {
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
                undefined,
                contentBlockId,
              );
            },
          },
        ],
      },
    ],
    [contentBlockId],
  );

  const validateContent = useCallback((): boolean => {
    let isValid = true;
    rules.forEach(({ id, test, message, actions }) => {
      // Check both content and template content for the rule.
      if (test(content + templateContent)) {
        addValidationNotice(id, message, actions);
        isValid = false;
      } else if (hasValidationNotice(id)) {
        removeValidationNotice(id);
      }
    });
    return isValid;
  }, [
    content,
    templateContent,
    addValidationNotice,
    removeValidationNotice,
    hasValidationNotice,
    rules,
  ]);

  const debouncedValidateContent = useDebounce(validateContent, 500);

  // Subscribe to updates so notices can be dismissed once resolved.
  subscribe(() => {
    if (!hasValidationNotice()) {
      return;
    }
    debouncedValidateContent();
  }, emailEditorStore);

  return {
    isValid: hasValidationNotice(),
    validateContent,
  };
};

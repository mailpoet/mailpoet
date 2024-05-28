import { useCallback } from '@wordpress/element';
import { useSelect, dispatch } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { EmailTheme, storeName } from '../store';

export function useEmailTheme() {
  const { templateTheme, templateId } = useSelect((select) => {
    const currentPostType = select(editorStore).getCurrentPostType();
    let templateThemeData: EmailTheme = {};
    let tId = null;
    let tContent = '';
    // Edit email post mode
    if (currentPostType === 'mailpoet_email') {
      const template = select(storeName).getEditedPostTemplate();
      // @ts-expect-error Todo types for template with email_theme
      templateThemeData = template?.mailpoet_email_theme || {};
      // @ts-expect-error Todo types for template with email_theme
      tId = template?.id;
      // @ts-expect-error Todo types for template
      tContent = template?.content;
    } else {
      // @ts-expect-error Todo types for template with email_theme
      templateThemeData =
        // @ts-expect-error Property 'getCurrentPostAttribute' has no types
        select(editorStore).getCurrentPostAttribute('mailpoet_email_theme') ||
        {};
      // @ts-expect-error Todo types for template with email_theme
      templateThemeData =
        // @ts-expect-error Property 'getCurrentPostAttribute' has no types
        select(editorStore).getCurrentPostAttribute('mailpoet_email_theme') ||
        {};
    }
    return {
      templateTheme: templateThemeData,
      templateId: tId,
      templateContent: tContent,
    };
  }, []);

  const updateTemplateTheme = useCallback(
    (newTheme) => {
      if (!templateId) {
        return;
      }
      void dispatch(coreStore).editEntityRecord(
        'postType',
        'wp_template',
        templateId as string,
        {
          mailpoet_email_theme: newTheme,
        },
      );
    },
    [templateId],
  );

  return {
    templateTheme,
    updateTemplateTheme,
  };
}

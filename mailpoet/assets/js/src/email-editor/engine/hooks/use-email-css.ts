import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useEntityProp } from '@wordpress/core-data';
import { merge } from 'lodash';
import {
  // @ts-expect-error No types for this exist yet.
  privateApis as blockEditorPrivateApi,
} from '@wordpress/block-editor';
import { unlock } from '../../lock-unlock';
import { EmailStyles, storeName } from '../store';

const { useGlobalStylesOutputWithConfig } = unlock(blockEditorPrivateApi);

export function useEmailCss() {
  const { theme, templateTheme } = useSelect((select) => {
    // @ts-expect-error Property 'getCurrentPostType' has no types
    const currentPostType = select(editorStore).getCurrentPostType();
    let templateThemeData = {};
    // Edit email post mode
    if (currentPostType === 'mailpoet_email') {
      const template = select(storeName).getEditedPostTemplate();
      // @ts-expect-error Todo types for template with email_theme
      templateThemeData = template?.email_theme?.theme || {};
    } else {
      // Edit email template mode
      templateThemeData =
        // @ts-expect-error Property 'getCurrentPostAttribute' has no types
        (select(editorStore).getCurrentPostAttribute('email_theme') || {})
          ?.theme || {};
    }
    return {
      theme: select(storeName).getTheme(),
      templateTheme: templateThemeData,
    };
  }, []);

  const [meta] = useEntityProp('postType', 'mailpoet_email', 'meta');

  const mergedConfig = useMemo(
    () =>
      merge(
        {},
        theme,
        templateTheme,
        meta?.mailpoet_email_theme,
      ) as EmailStyles,
    [theme, meta, templateTheme],
  );

  const [styles] = useGlobalStylesOutputWithConfig(mergedConfig);

  // eslint-disable-next-line @typescript-eslint/no-unsafe-return
  return [styles];
}

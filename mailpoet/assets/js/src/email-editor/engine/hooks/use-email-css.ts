import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
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
  const { theme } = useSelect(
    (select) => ({
      theme: select(storeName).getTheme(),
    }),
    [],
  );

  const [meta] = useEntityProp('postType', 'mailpoet_email', 'meta');

  const mergedConfig = useMemo(
    () => merge({}, theme, meta?.mailpoet_email_theme) as EmailStyles,
    [theme, meta],
  );

  const [styles] = useGlobalStylesOutputWithConfig(mergedConfig);

  // eslint-disable-next-line @typescript-eslint/no-unsafe-return
  return [styles];
}

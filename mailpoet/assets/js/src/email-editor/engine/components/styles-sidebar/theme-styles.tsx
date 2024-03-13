import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { merge } from 'lodash';
import {
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore No types for this exist yet.
  __unstableEditorStyles as EditorStyles,
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore No types for this exist yet.
  privateApis as blockEditorPrivateApi,
} from '@wordpress/block-editor';
import { unlock } from '../../../lock-unlock';
import { EmailStyles, storeName } from '../../store';

const { useGlobalStylesOutputWithConfig } = unlock(blockEditorPrivateApi);

export function ThemeStyles(): JSX.Element {
  const { theme } = useSelect(
    (select) => ({
      theme: select(storeName).getTheme(),
    }),
    [],
  );

  const [mailpoetEmailData] = useEntityProp(
    'postType',
    'mailpoet_email',
    'mailpoet_data',
  );

  const mergedConfig = useMemo(
    () => merge({}, theme, mailpoetEmailData?.theme) as EmailStyles,
    [theme, mailpoetEmailData],
  );

  const [styles] = useGlobalStylesOutputWithConfig(mergedConfig);

  return <EditorStyles styles={styles} scope=".editor-styles-wrapper" />;
}

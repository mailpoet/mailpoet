import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import deepmerge from 'deepmerge';
import {
  // @ts-expect-error No types for this exist yet.
  privateApis as blockEditorPrivateApi,
} from '@wordpress/block-editor';
import { unlock } from '../../lock-unlock';
import { EmailStyles, storeName } from '../store';
import { useEmailTheme } from './use-email-theme';

const { useGlobalStylesOutputWithConfig } = unlock(blockEditorPrivateApi);

export function useEmailCss() {
  const { templateTheme } = useEmailTheme();
  const { editorTheme } = useSelect(
    (select) => ({
      editorTheme: select(storeName).getTheme(),
    }),
    [],
  );

  const mergedConfig = useMemo(
    () =>
      deepmerge.all([
        {},
        editorTheme || {},
        templateTheme || {},
      ]) as EmailStyles,
    [editorTheme, templateTheme],
  );

  const [styles] = useGlobalStylesOutputWithConfig(mergedConfig);

  // eslint-disable-next-line @typescript-eslint/no-unsafe-return
  return [styles];
}

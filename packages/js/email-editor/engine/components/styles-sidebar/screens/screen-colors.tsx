import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import {
  // @ts-expect-error No types for this exist yet.
  privateApis as blockEditorPrivateApis,
} from '@wordpress/block-editor';
import ScreenHeader from './screen-header';
import { unlock } from '../../../../lock-unlock';
import { useEmailStyles } from '../../../hooks';
import { storeName } from '../../../store';

export function ScreenColors(): JSX.Element {
  const { ColorPanel: StylesColorPanel } = unlock(blockEditorPrivateApis);
  const { styles, defaultStyles, updateStyles } = useEmailStyles();
  const theme = useSelect((select) => select(storeName).getTheme(), []);

  return (
    <>
      <ScreenHeader
        title={__('Colors', 'mailpoet')}
        description={__(
          'Manage palettes and the default color of different global elements.',
          'mailpoet',
        )}
      />
      <StylesColorPanel
        value={styles}
        inheritValue={defaultStyles}
        onChange={updateStyles}
        settings={theme?.settings}
        panelId="colors"
      />
    </>
  );
}

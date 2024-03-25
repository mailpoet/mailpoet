import {
  // @ts-expect-error TS7016: Could not find a declaration file for module '@wordpress/block-editor'.
  __experimentalFontFamilyControl as FontFamilyControl,
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import {
  __experimentalToolsPanel as ToolsPanel,
  __experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';
import { useEmailStyles } from '../../../hooks';

export function TypographyPanel({ element }) {
  const { styles, defaultStyles } = useEmailStyles();

  const { fontFamily } = styles.typography;

  return (
    <ToolsPanel label={element} resetAll={() => {}}>
      <ToolsPanelItem
        label={__('Font family')}
        hasValue={() => fontFamily !== defaultStyles.typography.fontFamily}
        onDeselect={() => {}}
        isShownByDefault
      >
        <FontFamilyControl
          value={fontFamily}
          onChange={() => {}}
          size="__unstable-large"
          __nextHasNoMarginBottom
        />
      </ToolsPanelItem>
    </ToolsPanel>
  );
}

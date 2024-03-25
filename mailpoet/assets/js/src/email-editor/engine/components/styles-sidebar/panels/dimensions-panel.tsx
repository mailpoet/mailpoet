import {
  // We can remove the ts-expect-error comments once the types are available.
  // @ts-expect-error TS7016: Could not find a declaration file for module '@wordpress/block-editor'.
  __experimentalSpacingSizesControl as SpacingSizesControl,
  useSetting,
} from '@wordpress/block-editor';
import {
  __experimentalToolsPanel as ToolsPanel,
  __experimentalToolsPanelItem as ToolsPanelItem,
  __experimentalUseCustomUnits as useCustomUnits,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { isEqual } from 'lodash';
import { useEmailStyles } from '../../../hooks';

export function DimensionsPanel() {
  const availableUnits = useSetting('spacing.units') as string[];
  const units = useCustomUnits({
    availableUnits,
  });
  const { styles, defaultStyles, updateSpacingProp, resetSpacingProp } =
    useEmailStyles();

  return (
    <ToolsPanel
      label={__('Dimensions', 'mailpoet')}
      resetAll={() => {
        resetSpacingProp();
      }}
    >
      <ToolsPanelItem
        isShownByDefault
        hasValue={() =>
          !isEqual(styles.spacing.padding, defaultStyles.spacing.padding)
        }
        label={__('Padding')}
        onDeselect={() => resetSpacingProp('padding')}
        className="tools-panel-item-spacing"
      >
        <SpacingSizesControl
          allowReset
          values={styles.spacing.padding}
          onChange={(value) => {
            updateSpacingProp('padding', value);
          }}
          label={__('Padding')}
          sides={['horizontal', 'vertical', 'top', 'left', 'right', 'bottom']}
          units={units}
        />
      </ToolsPanelItem>
      <ToolsPanelItem
        isShownByDefault
        label={__('Block spacing')}
        hasValue={() =>
          styles.spacing.blockGap !== defaultStyles.spacing.blockGap
        }
        onDeselect={() => resetSpacingProp('blockGap')}
        className="tools-panel-item-spacing"
      >
        <SpacingSizesControl
          label={__('Block spacing')}
          min={0}
          onChange={(value) => {
            updateSpacingProp('blockGap', value.top);
          }}
          showSideInLabel={false}
          sides={['top']} // Use 'top' as the shorthand property in non-axial configurations.
          values={{ top: styles.spacing.blockGap }}
          allowReset
        />
      </ToolsPanelItem>
    </ToolsPanel>
  );
}

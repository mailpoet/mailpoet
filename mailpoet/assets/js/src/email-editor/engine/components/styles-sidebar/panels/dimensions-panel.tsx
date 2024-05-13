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
  const { styles, defaultStyles, updateStyleProp } = useEmailStyles();

  return (
    <ToolsPanel
      label={__('Dimensions', 'mailpoet')}
      resetAll={() => {
        updateStyleProp(['spacing'], defaultStyles.spacing);
      }}
    >
      <ToolsPanelItem
        isShownByDefault
        hasValue={() =>
          !isEqual(styles.spacing.padding, defaultStyles.spacing.padding)
        }
        label={__('Padding', 'mailpoet')}
        onDeselect={() =>
          updateStyleProp(['spacing', 'padding'], defaultStyles.spacing.padding)
        }
        className="tools-panel-item-spacing"
      >
        <SpacingSizesControl
          allowReset
          values={styles.spacing.padding}
          onChange={(value) => {
            updateStyleProp(['spacing', 'padding'], value);
          }}
          label={__('Padding', 'mailpoet')}
          sides={['horizontal', 'vertical', 'top', 'left', 'right', 'bottom']}
          units={units}
        />
      </ToolsPanelItem>
      <ToolsPanelItem
        isShownByDefault
        label={__('Block spacing', 'mailpoet')}
        hasValue={() =>
          styles.spacing.blockGap !== defaultStyles.spacing.blockGap
        }
        onDeselect={() =>
          updateStyleProp(
            ['spacing', 'blockGap'],
            defaultStyles.spacing.blockGap,
          )
        }
        className="tools-panel-item-spacing"
      >
        <SpacingSizesControl
          label={__('Block spacing', 'mailpoet')}
          min={0}
          onChange={(value) => {
            updateStyleProp(['spacing', 'blockGap'], value.top);
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

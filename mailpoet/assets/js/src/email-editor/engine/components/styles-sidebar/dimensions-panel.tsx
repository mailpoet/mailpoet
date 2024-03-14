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

export function DimensionsPanel() {
  const availableUnits = useSetting('spacing.units') as string[];
  const units = useCustomUnits({
    availableUnits,
  });

  // Padding
  const paddingValues = {
    top: '20px',
    right: '20px',
    bottom: '20px',
    left: '20px',
  };
  const resetPadding = () => {};
  const setPaddingValues = () => {};

  // Block spacing
  const blockGapValue = '16px';
  const resetBlockGap = () => {};
  const setBlockGapValue = () => {};
  const resetAll = () => {};

  return (
    <ToolsPanel label={__('Dimensions', 'mailpoet')} resetAll={resetAll}>
      <ToolsPanelItem
        isShownByDefault
        hasValue={() => true}
        label={__('Padding')}
        onDeselect={() => resetPadding()}
        className="tools-panel-item-spacing"
      >
        <SpacingSizesControl
          allowReset
          values={paddingValues}
          onChange={setPaddingValues}
          label={__('Padding')}
          sides={['horizontal', 'vertical', 'top', 'left', 'right', 'bottom']}
          units={units}
        />
      </ToolsPanelItem>
      <ToolsPanelItem
        isShownByDefault
        label={__('Block spacing')}
        hasValue={() => true}
        onDeselect={() => resetBlockGap()}
        className="tools-panel-item-spacing"
      >
        <SpacingSizesControl
          label={__('Block spacing')}
          min={0}
          onChange={setBlockGapValue}
          showSideInLabel={false}
          sides={['top']} // Use 'top' as the shorthand property in non-axial configurations.
          values={{ top: blockGapValue }}
          allowReset
        />
      </ToolsPanelItem>
    </ToolsPanel>
  );
}

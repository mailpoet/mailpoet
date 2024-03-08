import {
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  __experimentalSpacingSizesControl as SpacingSizesControl,
  useSetting,
} from '@wordpress/block-editor';
import { __experimentalToolsPanel as ToolsPanel, __experimentalToolsPanelItem as ToolsPanelItem, __experimentalUseCustomUnits as useCustomUnits, } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { isEqual } from 'lodash';
import { storeName } from '../../store';


const DEFAULT_PADDING = {
  bottom: '20px',
  left: '20px',
  right: '20px',
  top: '20px',
};

const DEFAULT_BLOCK_GAP = '16px';

export function DimensionsPanel() {
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  const availableUnits: string[] = useSetting('spacing.units');
  const units = useCustomUnits({
    availableUnits
  });

  const {styles} = useSelect((select) => ({
    styles: select(storeName).getStyles(),
  }));

  const {
    updateStyles,
  } = useDispatch(storeName);

  // Padding
  const paddingValues = styles.spacing.padding;
  const resetPadding = () => {
    void updateStyles({
      ...styles,
      spacing: {
        ...styles.spacing,
        padding: DEFAULT_PADDING,
      },
    });
  }
  const setPaddingValues = (value) => {
    void updateStyles({
      ...styles,
      spacing: {
        ...styles.spacing,
        padding: value,
      },
    })
  };

  // Block spacing
  const blockGapValue = styles.spacing.blockGap;
  const resetBlockGap = () => {
    void updateStyles({
      ...styles,
      spacing: {
        ...styles.spacing,
        blockGap: DEFAULT_BLOCK_GAP,
      },
    });
  }
  const setBlockGapValue = (value) => {
    void updateStyles({
      ...styles,
      spacing: {
        ...styles.spacing,
        blockGap: value.top || DEFAULT_BLOCK_GAP,
      },
    });
  };

  const resetAll = () => {
    void updateStyles({
      ...styles,
      spacing: {
        ...styles.spacing,
        blockGap: DEFAULT_BLOCK_GAP,
        padding: DEFAULT_PADDING,
      },
    });
  }

  return <ToolsPanel
    label={__('Dimensions', 'mailpoet')}
    resetAll={resetAll}
  >
    <ToolsPanelItem
      isShownByDefault
      hasValue={() => !isEqual(paddingValues, DEFAULT_PADDING)}
      label={__('Padding')}
      onDeselect={() => resetPadding()}
      className="tools-panel-item-spacing"
    >
      <SpacingSizesControl
        allowReset
        values={paddingValues}
        onChange={setPaddingValues}
        label={__('Padding', 'mailpoet')}
        sides={['horizontal', 'vertical', 'top', 'left', 'right', 'bottom']}
        units={units}
      />
    </ToolsPanelItem>
    <ToolsPanelItem
      isShownByDefault
      label={__('Block spacing', 'mailpoet')}
      hasValue={() => blockGapValue !== DEFAULT_BLOCK_GAP}
      onDeselect={() => resetBlockGap()}
      className="tools-panel-item-spacing"
    >
      <SpacingSizesControl
        label={__('Block spacing', 'mailpoet')}
        min={0}
        onChange={setBlockGapValue}
        showSideInLabel={false}
        sides={['top']} // Use 'top' as the shorthand property in non-axial configurations.
        values={{top: blockGapValue}}
        allowReset
      />
    </ToolsPanelItem>
  </ToolsPanel>;
}

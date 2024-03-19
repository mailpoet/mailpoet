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
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { isEqual } from 'lodash';
import { EmailTheme, storeName } from '../../store';

export function DimensionsPanel() {
  const availableUnits = useSetting('spacing.units') as string[];
  const units = useCustomUnits({
    availableUnits,
  });

  const [meta, setMeta] = useEntityProp('postType', 'mailpoet_email', 'meta');
  const emailTheme = meta?.mailpoet_email_theme as EmailTheme;
  const updateEmailTheme = (newValue) => {
    setMeta({ ...meta, mailpoet_email_theme: newValue });
  };

  const { styles } = useSelect((select) => ({
    styles: select(storeName).getStyles(),
  }));
  const defaultPadding = styles.spacing.padding ?? undefined;
  const defaultBlockGap = styles.spacing.blockGap ?? undefined;

  // Padding
  const paddingValues = emailTheme?.styles?.spacing?.padding ?? defaultPadding;
  const resetPadding = () => {
    updateEmailTheme({
      ...emailTheme,
      styles: {
        ...emailTheme?.styles,
        spacing: {
          ...emailTheme?.styles?.spacing,
          padding: defaultPadding ?? undefined,
        },
      },
    });
  };
  const setPaddingValues = (value) => {
    updateEmailTheme({
      ...emailTheme,
      styles: {
        ...emailTheme?.styles,
        spacing: {
          ...emailTheme?.styles?.spacing,
          padding: value,
        },
      },
    });
  };

  // Block spacing
  const blockGapValue =
    emailTheme?.styles?.spacing?.blockGap ?? defaultBlockGap;
  const resetBlockGap = () => {
    updateEmailTheme({
      ...emailTheme,
      styles: {
        ...emailTheme?.styles,
        spacing: {
          ...styles.spacing,
          blockGap: undefined,
        },
      },
    });
  };

  const setBlockGapValue = (value) => {
    updateEmailTheme({
      ...emailTheme,
      styles: {
        ...emailTheme?.styles,
        spacing: {
          ...emailTheme?.styles?.spacing,
          blockGap: value.top || styles.spacing.blockGap,
        },
      },
    });
  };

  const resetAll = () => {
    updateEmailTheme({
      ...emailTheme,
      styles: {
        ...emailTheme?.styles,
        spacing: {
          ...styles.spacing,
          padding: defaultPadding ?? undefined,
          blockGap: defaultBlockGap ?? undefined,
        },
      },
    });
  };

  return (
    <ToolsPanel label={__('Dimensions', 'mailpoet')} resetAll={resetAll}>
      <ToolsPanelItem
        isShownByDefault
        hasValue={() => !isEqual(paddingValues, defaultPadding)}
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
        hasValue={() => blockGapValue !== defaultBlockGap}
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

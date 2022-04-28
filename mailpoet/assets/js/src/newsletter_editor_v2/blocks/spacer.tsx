import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { PanelBody, ColorPicker } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

const defaultBgColor = '#fff';
export const registerSpacer = () => {
  const addAttributes = (settings, blockName) => {
    if (blockName !== 'core/spacer') {
      return settings;
    }

    return {
      ...settings,
      attributes: {
        ...settings.attributes,
        backgroundColor: {
          type: 'string',
          default: defaultBgColor,
        },
      },
    };
  };

  const withBackgroundColorSettings = createHigherOrderComponent(
    (BlockEdit) =>
      function SpacerWithBackgroundColor(props) {
        if (props.name !== 'core/spacer') {
          return <BlockEdit {...props} />;
        }
        const {
          attributes: { backgroundColor },
          setAttributes,
        } = props;
        const clientId = props.clientId as string;
        const styles = backgroundColor
          ? `
            #block-${clientId} {
              background-color: ${backgroundColor as string}
            }
          `
          : '';

        return (
          <>
            <BlockEdit {...props} />
            <InspectorControls>
              <PanelBody title={__('Background Color')}>
                <ColorPicker
                  color={backgroundColor}
                  enableAlpha
                  copyFormat="hex"
                  onChange={(bgColor) =>
                    setAttributes({ backgroundColor: bgColor })
                  }
                  defaultValue={defaultBgColor}
                />
              </PanelBody>
            </InspectorControls>
            <style id={`${clientId}-styles`}>{styles}</style>
          </>
        );
      },
    'withBackgroundColorSettings',
  );

  const withBackgroundColorProps = (props, blockType, attributes) => {
    if (blockType.name !== 'core/spacer' || !attributes.backgroundColor) {
      return props;
    }

    const { backgroundColor } = attributes;
    return {
      ...props,
      style: {
        ...props.style,
        backgroundColor,
      },
    };
  };

  addFilter(
    'blocks.registerBlockType',
    'mailpoet/spacer-add-settings',
    addAttributes,
  );

  addFilter(
    'editor.BlockEdit',
    'mailpeot/spacer-settings-edit',
    withBackgroundColorSettings,
  );

  addFilter(
    'blocks.getSaveContent.extraProps',
    'mailpeot/spacer-settings-extraProps',
    withBackgroundColorProps,
  );
};

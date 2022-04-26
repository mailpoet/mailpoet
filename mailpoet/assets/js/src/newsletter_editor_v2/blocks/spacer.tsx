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

  addFilter(
    'blocks.registerBlockType',
    'mailpoet/spacer-add-settings',
    addAttributes,
  );

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
        return (
          <>
            <BlockEdit {...props} />
            <InspectorControls>
              <PanelBody title={__('Background Color')}>
                <ColorPicker
                  color={backgroundColor}
                  enableAlpha
                  copyFormat="rgb"
                  onChange={(bgColor) =>
                    setAttributes({ backgroundColor: bgColor })
                  }
                  defaultValue={defaultBgColor}
                />
              </PanelBody>
            </InspectorControls>
          </>
        );
      },
    'withBackgroundColorSettings',
  );

  const withBackgroundColorProps = (element, blockType, attributes) => {
    if (blockType.name !== 'core/spacer' || !attributes.backgroundColor) {
      return element;
    }

    // eslint-disable-next-line no-param-reassign
    element.props.style.backgroundColor = attributes.backgroundColor;

    return element;
  };

  addFilter(
    'editor.BlockEdit',
    'mailpeot/spacer-settings-edit',
    withBackgroundColorSettings,
  );

  addFilter(
    'blocks.getSaveElement',
    'mailpeot/spacer-settings-save1',
    withBackgroundColorProps,
  );
};

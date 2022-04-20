import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { PanelBody, SelectControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { ReactElement } from 'react';

export const registerButton = () => {
  const withBorderSettings = createHigherOrderComponent(
    (BlockEdit) =>
      function ButtonWithBorderEdit(props) {
        if (props.name !== 'core/button') {
          return <BlockEdit {...props} />;
        }
        const { attributes, setAttributes } = props;
        return (
          <>
            <BlockEdit {...props} />
            <InspectorControls>
              <PanelBody title={__('Border')}>
                <SelectControl
                  label={__('Border Style')}
                  value={attributes.style.border.style}
                  options={[
                    {
                      label: __('None'),
                      value: '',
                    },
                    {
                      label: __('Solid'),
                      value: 'solid',
                    },
                    {
                      label: __('Dashed'),
                      value: 'dashed',
                    },
                  ]}
                  onChange={(value) => {
                    setAttributes({
                      style: {
                        ...attributes.style,
                        border: {
                          ...attributes.style.border,
                          style: value,
                          color: '#ff0000',
                          width: '2px',
                        },
                      },
                    });
                  }}
                />
              </PanelBody>
            </InspectorControls>
          </>
        );
      },
    'withBorderSettings',
  );

  const savePropsBorderSettings = (extraProps, blockType, attributes) => {
    if (blockType.name !== 'core/button') {
      return extraProps;
    }
    const { borderStyle } = attributes;
    if (borderStyle) {
      // eslint-disable-next-line no-param-reassign
      attributes.style = {
        ...attributes.style,
        border: {
          ...attributes.style.border,
          style: 'solid',
          color: '#ff000',
          width: '2px',
        },
      };
    }
    return extraProps;
  };

  const saveElementBorderSettings = (
    element: ReactElement,
    blockType,
    attributes,
  ) => {
    if (blockType.name !== 'core/button') {
      return element;
    }
    const { borderStyle } = attributes;
    if (borderStyle) {
      // eslint-disable-next-line no-param-reassign
      element.props.children.props.style.borderStyle = borderStyle;
      // eslint-disable-next-line no-param-reassign
      element.props.children.props.style.borderWidth = '2px';
      // eslint-disable-next-line no-param-reassign
      element.props.children.props.style.borderColor = '#ff000';
    }
    return element;
  };

  addFilter(
    'editor.BlockEdit',
    'mailpeot/button-modifications-settings',
    withBorderSettings,
  );

  addFilter(
    'blocks.getSaveContent.extraProps',
    'mailpeot/button-modifications-save',
    savePropsBorderSettings,
  );

  addFilter(
    'blocks.getSaveElement',
    'mailpeot/button-modifications-save',
    saveElementBorderSettings,
  );
};

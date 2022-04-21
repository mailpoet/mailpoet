import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { PanelBody, TextControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

export const registerColumns = () => {
  const withBackgroundImageSettings = createHigherOrderComponent(
    (BlockEdit) =>
      function ButtonWithBorderEdit(props) {
        if (props.name !== 'core/columns') {
          return <BlockEdit {...props} />;
        }
        const { attributes, setAttributes } = props;
        const css = attributes.style?.backgroundImage
          ? `
            #block-${props.clientId as string} {
              background-image: url(${
                attributes.style?.backgroundImage as string
              });
            }
          `
          : '';
        return (
          <>
            <BlockEdit {...props} />
            <InspectorControls>
              <PanelBody title={__('Background Image')}>
                <TextControl
                  label={__('Background Image URL')}
                  value={attributes.style?.backgroundImage}
                  onChange={(value) => {
                    setAttributes({
                      style: {
                        ...attributes.style,
                        backgroundImage: value,
                      },
                    });
                  }}
                />
              </PanelBody>
            </InspectorControls>
            <style>{css}</style>
          </>
        );
      },
    'withBackgroundImageSettings',
  );

  addFilter(
    'editor.BlockEdit',
    'mailpeot/columns-modifications-settings',
    withBackgroundImageSettings,
  );
};

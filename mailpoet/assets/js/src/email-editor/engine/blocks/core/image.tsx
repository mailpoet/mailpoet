import { InspectorControls } from '@wordpress/block-editor';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';

const imageEditCallback = createHigherOrderComponent(
  (BlockEdit) =>
    function alterBlocksEdits(props) {
      if (props.name !== 'core/image') {
        return <BlockEdit {...props} />;
      }
      // Because we cannot support displaying the modal with image after clicking in the email we have to hide the toggle
      const deactivateToggleCss = `
        .components-tools-panel .components-toggle-control { display: none; }
      `;

      return (
        <>
          <BlockEdit {...props} />
          <InspectorControls>
            <style>{deactivateToggleCss}</style>
          </InspectorControls>
        </>
      );
    },
  'imageEditCallback',
);

/**
 * Because CSS property filter is not supported in almost 50% of email clients we have to disable it
 */
function disableImageFilter() {
  addFilter(
    'blocks.registerBlockType',
    'mailpoet-email-editor/deactivate-image-filter',
    (settings, name) => {
      if (name === 'core/image') {
        // eslint-disable-next-line @typescript-eslint/no-unsafe-return
        return {
          ...settings,
          supports: {
            ...settings.supports,
            filter: {
              duetone: false,
            },
          },
        };
      }

      // eslint-disable-next-line @typescript-eslint/no-unsafe-return
      return settings;
    },
  );
}

function hideExpandOnClick() {
  addFilter(
    'editor.BlockEdit',
    'mailpoet-email-editor/hide-expand-on-click',
    imageEditCallback,
  );
}

export { hideExpandOnClick, disableImageFilter };

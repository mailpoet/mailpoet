import { InspectorControls } from '@wordpress/block-editor';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';

const columnsEditCallback = createHigherOrderComponent(
  (BlockEdit) =>
    function alterBlocksEdits(props) {
      if (props.name !== 'core/columns') {
        return <BlockEdit {...props} />;
      }
      // CSS sets opacity by the class is-disabled by the toggle component from the Gutenberg package
      // To deactivating the input we use CSS pointer-events because we want to avoid JavaScript hacks
      const deactivateToggleCss = `
      .components-panel__body .components-toggle-control .components-form-toggle { opacity: 0.3; }
      .components-panel__body .components-toggle-control .components-form-toggle__input { pointer-events: none; }
      .components-panel__body .components-toggle-control label { pointer-events: none; }
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
  'columnsEditCallback',
);

function deactivateStackOnMobile() {
  addFilter(
    'editor.BlockEdit',
    'mailpoet-email-editor/deactivate-stack-on-mobile',
    // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
    columnsEditCallback,
  );
}

export { deactivateStackOnMobile };

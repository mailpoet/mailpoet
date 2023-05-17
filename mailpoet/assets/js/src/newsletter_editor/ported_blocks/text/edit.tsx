import { useCallback, useRef } from '@wordpress/element';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { TextBlock } from 'newsletter_editor/blocks/text';

export function Edit() {
  const model = useRef(new TextBlock.TextBlockModel());
  console.log('Text rendering');
  const elemRef = useCallback(
    (el) => {
      try {
        const text = new TextBlock.TextBlockView({
          el,
          model: model.current,
        });
        // eslint-disable-next-line no-console
        console.log(text.render(model.current));

        document.dispatchEvent(new Event('mailpoet:startEditor'));
      } catch (e) {
        // eslint-disable-next-line no-console
        console.log({ e });
      }
    },
    [model],
  );

  const elemSettingsRef = useCallback(
    (el) => {
      try {
        const settings = new TextBlock.TextBlockSettingsView({
          el,
          model: model.current,
          renderOptions: {
            displayFormat: 'element',
          },
        });
        // eslint-disable-next-line no-console
        console.log(settings.render(model.current));
      } catch (e) {
        // eslint-disable-next-line no-console
        console.log({ e });
      }
    },
    [model],
  );

  return (
    <div {...useBlockProps()}>
      <InspectorControls key="setting">
        <p>Controls</p>
        <div>
          <div ref={elemSettingsRef} />
        </div>
      </InspectorControls>
      <div className="mailpoet_block mailpoet_text_block" ref={elemRef} />
    </div>
  );
}

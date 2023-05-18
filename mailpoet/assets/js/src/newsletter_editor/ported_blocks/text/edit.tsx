import { useCallback, useEffect, useRef } from '@wordpress/element';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { TextBlock } from 'newsletter_editor/blocks/text';

export function Edit({ setAttributes, attributes }) {
  const model = useRef(
    new TextBlock.TextBlockModel(attributes.legacyBlockData),
  );

  useEffect(() => {
    model.current.listenTo(model.current, 'change', () => {
      setAttributes({ legacyBlockData: model.current.attributes });
    });
  }, [model, setAttributes]);

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

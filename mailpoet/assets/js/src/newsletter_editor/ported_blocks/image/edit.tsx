import { useCallback, useRef, useEffect } from '@wordpress/element';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { ImageBlock } from 'newsletter_editor/blocks/image';

export function Edit({ setAttributes, attributes }) {
  const model = useRef(
    new ImageBlock.ImageBlockModel(attributes.legacyBlockData),
  );
  useEffect(() => {
    model.current.listenTo(model.current, 'change', () => {
      setAttributes({ legacyBlockData: model.current.attributes });
    });
  }, [model, setAttributes]);

  const elemRef = useCallback(
    (el) => {
      try {
        const image = new ImageBlock.ImageBlockView({
          el,
          model: model.current,
        });
        // eslint-disable-next-line no-console
        console.log(image.render(model.current));
      } catch (e) {
        // eslint-disable-next-line no-console
        console.log({ e });
      }
    },
    [model],
  );

  const elemSettings = useCallback(
    (el) => {
      try {
        const imageSettings = new ImageBlock.ImageBlockSettingsView({
          el,
          model: model.current,
          renderOptions: {
            displayFormat: 'element',
          },
        });
        // eslint-disable-next-line no-console
        console.log(imageSettings.render(model.current));
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
          <div ref={elemSettings} />
        </div>
      </InspectorControls>
      <div className="mailpoet_block mailpoet_image_block" ref={elemRef} />
    </div>
  );
}

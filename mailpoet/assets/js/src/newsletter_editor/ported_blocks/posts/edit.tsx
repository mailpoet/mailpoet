import { useCallback, useRef, useEffect } from '@wordpress/element';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PostsBlock } from 'newsletter_editor/blocks/posts';

export function Edit() {
  const model = useRef(new PostsBlock.PostsBlockModel());
  useEffect(() => {
    model.current.listenTo(model.current, 'change', () => {
      // console.log(model.current);
    });
  }, [model]);

  const elemRef = useCallback(
    (el) => {
      try {
        const image = new PostsBlock.PostsBlockView({
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
        const imageSettings = new PostsBlock.PostsBlockSettingsView({
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

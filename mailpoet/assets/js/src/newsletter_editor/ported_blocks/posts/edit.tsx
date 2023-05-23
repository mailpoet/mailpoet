import { dispatch } from '@wordpress/data';
import { useCallback, useRef } from '@wordpress/element';
import {
  useBlockProps,
  InspectorControls,
  store as blockEditorStore,
} from '@wordpress/block-editor';
import { PostsBlock } from 'newsletter_editor/blocks/posts';

export function Edit({ clientId }) {
  const model = useRef(new PostsBlock.PostsBlockModel());
  const elemRef = useCallback(
    (el) => {
      try {
        const view = new PostsBlock.PostsBlockView({
          el,
          model: model.current,
          insertCallback: () => {},
        });
        // eslint-disable-next-line no-console
        console.log({ postsView: view.render(model.current) });
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
        const settings = new PostsBlock.PostsBlockSettingsView({
          el,
          model: model.current,
          renderOptions: {
            displayFormat: 'element',
          },
          insertCallback: () => {
            const oldBlocks =
              // eslint-disable-next-line no-underscore-dangle
              model.current.attributes._transformedPosts.toJSON().blocks[0]
                .blocks[0].blocks;

            const mappedBLocks = oldBlocks.map((block, index) => ({
              // eslint-disable-next-line @typescript-eslint/restrict-template-expressions
              name: `mailpoet/${block.type}-ported-block`,
              clientId: `${clientId as string} ${index as string}`,
              attributes: {
                legacyBlockData: block,
              },
              innerBlocks: [],
              isValid: true,
            }));
            dispatch(blockEditorStore).replaceBlocks(
              clientId as string,
              // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
              mappedBLocks,
            );
          },
        });
        // eslint-disable-next-line no-console
        console.log({ postsSettings: settings.render(model.current) });
      } catch (e) {
        // eslint-disable-next-line no-console
        console.log({ e });
      }
    },
    [model, clientId],
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

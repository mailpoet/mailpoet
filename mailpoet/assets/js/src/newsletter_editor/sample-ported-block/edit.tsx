import { ImageBlock } from '../blocks/image';

const { useCallback } = window.wp.element;

export function Edit() {
  const elemRef = useCallback((el) => {
    try {
      const image = new ImageBlock.ImageBlockView({
        el,
        model: new ImageBlock.ImageBlockModel(),
      });
      // eslint-disable-next-line no-console
      console.log(image.render(new ImageBlock.ImageBlockModel()));
    } catch (e) {
      // eslint-disable-next-line no-console
      console.log({ e });
    }
  }, []);

  return <div ref={elemRef} />;
}

type Props = {
  color: string;
};

function Background({ color }: Props) {
  return (
    <style
      /* eslint-disable-next-line react/no-danger */
      dangerouslySetInnerHTML={{ __html: `body { background: ${color}; }` }}
    />
  );
}

export default Background;

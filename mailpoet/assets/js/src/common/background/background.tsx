import React from 'react';

type Props = {
  color: string;
};

function Background({ color }: Props) {
  return (
    <>
      {/* eslint-disable-next-line react/no-danger */}
      <style dangerouslySetInnerHTML={{ __html: `body { background: ${color}; }` }} />
    </>
  );
}

export default Background;

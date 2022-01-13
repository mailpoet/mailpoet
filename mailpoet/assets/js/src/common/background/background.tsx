import React from 'react';

type Props = {
  color: string;
};

const Background = ({ color }: Props) => (
  <>
    {/* eslint-disable-next-line react/no-danger */}
    <style dangerouslySetInnerHTML={{ __html: `body { background: ${color}; }` }} />
  </>
);

export default Background;

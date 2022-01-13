import React from 'react';

const HideScreenOptions = () => (
  <>
    {/* eslint-disable-next-line react/no-danger */}
    <style dangerouslySetInnerHTML={
      {
        __html: `
          #screen-meta { display: none !important; }
          #screen-meta-links { display: none; }
        `,
      }
    }
    />
  </>
);

export default HideScreenOptions;

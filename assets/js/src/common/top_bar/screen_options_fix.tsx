import React from 'react';

export const ScreenOptionsFix = () => (
  <>
    {/* eslint-disable-next-line react/no-danger */}
    <style dangerouslySetInnerHTML={{ __html: '#screen-meta { border: 0; margin: 0 -20px; }' }} />

    {/* eslint-disable-next-line react/no-danger */}
    <style dangerouslySetInnerHTML={
      {
        __html: `#screen-meta-links {
          margin-top: -50px;
          position: relative;
          top: 114px;
          z-index: 10;
        }`,
      }
    }
    />

    {/* eslint-disable-next-line react/no-danger */}
    <style dangerouslySetInnerHTML={{ __html: '#screen-meta-links .show-settings { border-color: #e5e9f8; }' }} />
  </>
);

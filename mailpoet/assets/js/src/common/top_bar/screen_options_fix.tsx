import React from 'react';

export const ScreenOptionsFix = () => (
  <>
    {/* eslint-disable-next-line react/no-danger */}
    <style dangerouslySetInnerHTML={{ __html: '#screen-meta { border: 0; margin: 0 -20px; }' }} />

    {/* eslint-disable-next-line react/no-danger */}
    <style dangerouslySetInnerHTML={
      {
        __html: `#screen-meta-links .show-settings {
          border-color: #e5e9f8;
          margin-bottom: 10px;
          position: relative;
          z-index: 1;
        }`,
      }
    }
    />

    {/* eslint-disable-next-line react/no-danger */}
    <style dangerouslySetInnerHTML={{ __html: '#wpbody-content { padding-top: 64px; }' }} />

    {/* eslint-disable-next-line react/no-danger */}
    <style dangerouslySetInnerHTML={{ __html: '.wrap { margin-top: 20px; }' }} />
  </>
);

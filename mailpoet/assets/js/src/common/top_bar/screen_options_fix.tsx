export function ScreenOptionsFix() {
  return (
    <>
      <style
        /* eslint-disable-next-line react/no-danger */
        dangerouslySetInnerHTML={{ __html: '#screen-meta { border: 0; margin: 0 -20px; }' }}
      />

      <style
        /* eslint-disable-next-line react/no-danger */
        dangerouslySetInnerHTML={{
        __html: `#screen-meta-links .show-settings {
          border-color: #e5e9f8;
          margin-bottom: 10px;
          position: relative;
          z-index: 1;
        }`,
      }}
      />

      <style
        /* eslint-disable-next-line react/no-danger */
        dangerouslySetInnerHTML={{ __html: '#wpbody-content { padding-top: 64px; }' }}
      />

      <style
        /* eslint-disable-next-line react/no-danger */
        dangerouslySetInnerHTML={{ __html: '.wrap { margin-top: 20px; }' }}
      />
    </>
  );
}

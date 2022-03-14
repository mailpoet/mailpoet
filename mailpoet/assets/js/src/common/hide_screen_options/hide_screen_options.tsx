function HideScreenOptions() {
  return (
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
}

export default HideScreenOptions;

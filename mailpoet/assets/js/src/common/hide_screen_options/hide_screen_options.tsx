function HideScreenOptions() {
  return (
    <style
      /* eslint-disable-next-line react/no-danger */
      dangerouslySetInnerHTML={{
      __html: `
        #screen-meta { display: none !important; }
        #screen-meta-links { display: none; }
      `,
    }}
    />
  );
}

export default HideScreenOptions;

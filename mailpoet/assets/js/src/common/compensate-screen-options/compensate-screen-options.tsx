export function CompensateScreenOptions() {
  return (
    <style
      /* eslint-disable-next-line react/no-danger */
      dangerouslySetInnerHTML={{
        __html: `
        #screen-meta-links { margin-bottom: -22px; }
      `,
      }}
    />
  );
}

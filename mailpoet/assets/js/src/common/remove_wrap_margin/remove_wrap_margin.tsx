function RemoveWrapMargin() {
  return (
    <style
      /* eslint-disable-next-line react/no-danger */
      dangerouslySetInnerHTML={{ __html: '.wrap { margin: 0 !important; }' }}
    />
  );
}

export default RemoveWrapMargin;

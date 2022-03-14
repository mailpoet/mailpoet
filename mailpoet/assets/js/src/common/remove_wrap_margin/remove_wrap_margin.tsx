function RemoveWrapMargin() {
  return (
    <>
      {/* eslint-disable-next-line react/no-danger */}
      <style dangerouslySetInnerHTML={{ __html: '.wrap { margin: 0 !important; }' }} />
    </>
  );
}

export default RemoveWrapMargin;

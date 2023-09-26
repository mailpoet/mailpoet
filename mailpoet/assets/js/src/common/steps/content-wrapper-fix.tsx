export function ContentWrapperFix() {
  return (
    <>
      <style
        /* eslint-disable-next-line react/no-danger */
        dangerouslySetInnerHTML={{
          __html: '#wpbody-content { padding-top: 73px; }',
        }}
      />

      <style
        /* eslint-disable-next-line react/no-danger */
        dangerouslySetInnerHTML={{ __html: '.wrap { margin-top: 20px; }' }}
      />
    </>
  );
}

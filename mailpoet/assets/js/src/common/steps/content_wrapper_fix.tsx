export function ContentWrapperFix() {
  return (
    <>
      {/* eslint-disable-next-line react/no-danger */}
      <style dangerouslySetInnerHTML={{ __html: '#wpbody-content { padding-top: 73px; }' }} />

      {/* eslint-disable-next-line react/no-danger */}
      <style dangerouslySetInnerHTML={{ __html: '.wrap { margin-top: 20px; }' }} />
    </>
  );
}

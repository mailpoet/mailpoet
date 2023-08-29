// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
import { __experimentalPreviewOptions as PreviewOptionsX } from '@wordpress/block-editor';
import { Button } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { store as editPostStore } from '@wordpress/edit-post';

export function MpPreviewOptions() {
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  const { __experimentalSetPreviewDeviceType: setPreviewDeviceType } =
    useDispatch(editPostStore);
  return (
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    <PreviewOptionsX
      deviceType="desktop"
      setDeviceType={setPreviewDeviceType}
      className="mailpoet-preview-dropdown"
    >
      <Button variant="secondary">Send Email</Button>
    </PreviewOptionsX>
  );
}

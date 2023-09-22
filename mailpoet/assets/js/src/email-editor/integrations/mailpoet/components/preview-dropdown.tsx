// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore - some of Gutenberg types are not available yet
import {
  MenuGroup,
  MenuItem,
  Button,
  DropdownMenu,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { store as editPostStore } from '@wordpress/edit-post';
import { Icon, external, check, mobile, desktop } from '@wordpress/icons';
import { SendPreviewEmail } from './send-preview-email';

type PreviewDropdownProps = {
  newsletterPreviewUrl: string | null;
};

export function PreviewDropdown({
  newsletterPreviewUrl,
}: PreviewDropdownProps) {
  // We use WP store at this moment, but if we use our own store in combination with our canvas
  // it's possible to use use-resize-canvas for resizing the canvas by the device type
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  const { __experimentalSetPreviewDeviceType: setPreviewDeviceType } =
    useDispatch(editPostStore);

  const [isModalOpen, setIsModalOpen] = useState<boolean>(false);
  const [deviceType, setDeviceType] = useState<string>('Desktop');

  const changeDeviceType = (newDeviceType: string) => {
    // eslint-disable-next-line react-hooks/rules-of-hooks
    setPreviewDeviceType(newDeviceType);
    setDeviceType(newDeviceType);
  };

  const openInNewTab = (url: string) => {
    window.open(url, '_blank', 'noreferrer');
  };

  const deviceIcons = {
    mobile,
    desktop,
  };

  return (
    <>
      <DropdownMenu
        className="mailpoet-preview-dropdown"
        label={__('Preview', 'mailpoet')}
        icon={deviceIcons[deviceType.toLowerCase()]}
      >
        {({ onClose }) => (
          <>
            <MenuGroup>
              <MenuItem
                className="block-editor-post-preview__button-resize"
                onClick={() => changeDeviceType('Desktop')}
                icon={deviceType === 'Desktop' && check}
              >
                {__('Desktop')}
              </MenuItem>
              <MenuItem
                className="block-editor-post-preview__button-resize"
                onClick={() => changeDeviceType('Mobile')}
                icon={deviceType === 'Mobile' && check}
              >
                {__('Mobile')}
              </MenuItem>
            </MenuGroup>
            <MenuGroup>
              <MenuItem
                className="block-editor-post-preview__button-resize"
                onClick={() => {
                  setIsModalOpen(true);
                  onClose();
                }}
              >
                {__('Send a test email', 'mailpoet')}
              </MenuItem>
            </MenuGroup>
            <MenuGroup>
              <div className="edit-post-header-preview__grouping-external">
                <Button
                  className="edit-post-header-preview__button-external"
                  onClick={() => {
                    openInNewTab(newsletterPreviewUrl);
                  }}
                >
                  {__('Preview in new tab')}
                  <Icon icon={external} />
                </Button>
              </div>
            </MenuGroup>
          </>
        )}
      </DropdownMenu>
      <SendPreviewEmail
        isOpen={isModalOpen}
        closeCallback={() => setIsModalOpen(false)}
      />
    </>
  );
}

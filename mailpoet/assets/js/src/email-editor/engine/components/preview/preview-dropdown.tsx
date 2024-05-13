import {
  MenuGroup,
  MenuItem,
  Button,
  DropdownMenu,
} from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Icon, external, check, mobile, desktop } from '@wordpress/icons';
import { SendPreviewEmail } from './send-preview-email';
import { storeName } from '../../store';

export function PreviewDropdown() {
  const [mailpoetEmailData] = useEntityProp(
    'postType',
    'mailpoet_email',
    'mailpoet_data',
  );

  const previewDeviceType = useSelect((select) => {
    const { deviceType } = select(storeName).getPreviewState();
    return deviceType;
  }, []);

  const { changePreviewDeviceType, togglePreviewModal } =
    useDispatch(storeName);
  const newsletterPreviewUrl: string = mailpoetEmailData?.preview_url || '';

  const changeDeviceType = (newDeviceType: string) => {
    void changePreviewDeviceType(newDeviceType);
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
        icon={deviceIcons[previewDeviceType.toLowerCase()]}
      >
        {({ onClose }) => (
          <>
            <MenuGroup>
              <MenuItem
                className="block-editor-post-preview__button-resize"
                onClick={() => changeDeviceType('Desktop')}
                icon={previewDeviceType === 'Desktop' && check}
              >
                {__('Desktop', 'mailpoet')}
              </MenuItem>
              <MenuItem
                className="block-editor-post-preview__button-resize"
                onClick={() => changeDeviceType('Mobile')}
                icon={previewDeviceType === 'Mobile' && check}
              >
                {__('Mobile', 'mailpoet')}
              </MenuItem>
            </MenuGroup>
            <MenuGroup>
              <MenuItem
                className="block-editor-post-preview__button-resize"
                onClick={() => {
                  void togglePreviewModal(true);
                  onClose();
                }}
              >
                {__('Send a test email', 'mailpoet')}
              </MenuItem>
            </MenuGroup>
            {newsletterPreviewUrl ? (
              <MenuGroup>
                <div className="edit-post-header-preview__grouping-external">
                  <Button
                    className="edit-post-header-preview__button-external components-menu-item__button"
                    onClick={() => {
                      openInNewTab(newsletterPreviewUrl);
                    }}
                  >
                    {__('Preview in new tab', 'mailpoet')}
                    <Icon icon={external} />
                  </Button>
                </div>
              </MenuGroup>
            ) : null}
          </>
        )}
      </DropdownMenu>
      <SendPreviewEmail />
    </>
  );
}

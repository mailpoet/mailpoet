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
import { storeName } from '../../store';

export function PreviewDropdown() {
  const [mailpoetEmailData] = useEntityProp(
    'postType',
    'mailpoet_email',
    'mailpoet_data',
  );

  const { previewDeviceType } = useSelect(
    (select) => ({
      previewDeviceType: select(storeName).getPreviewDeviceType(),
    }),
    [],
  );

  const { changePreviewDeviceType } = useDispatch(storeName);
  const newsletterPreviewUrl: string = mailpoetEmailData?.preview_url || '';

  const changeDeviceType = (newDeviceType: string) => {
    changePreviewDeviceType(newDeviceType);
  };

  const openInNewTab = (url: string) => {
    window.open(url, '_blank', 'noreferrer');
  };

  const deviceIcons = {
    mobile,
    desktop,
  };

  return (
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
              {__('Desktop')}
            </MenuItem>
            <MenuItem
              className="block-editor-post-preview__button-resize"
              onClick={() => changeDeviceType('Mobile')}
              icon={previewDeviceType === 'Mobile' && check}
            >
              {__('Mobile')}
            </MenuItem>
          </MenuGroup>
          <MenuGroup>
            <MenuItem
              className="block-editor-post-preview__button-resize"
              onClick={() => {
                // TODO: add opening modal here
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
          ) : null}
        </>
      )}
    </DropdownMenu>
  );
}

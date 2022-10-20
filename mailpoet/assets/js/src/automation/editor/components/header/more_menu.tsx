import { MenuGroup, MenuItem } from '@wordpress/components';
import { displayShortcut } from '@wordpress/keycodes';
import { __, _x } from '@wordpress/i18n';
import { MoreMenuDropdown } from '@wordpress/interface';
import { PreferenceToggleMenuItem } from '@wordpress/preferences';
import { storeName } from '../../store';
import { MailPoet } from '../../../../mailpoet';

// See:
//   https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/edit-post/src/components/header/more-menu/index.js
//   https://github.com/WordPress/gutenberg/blob/0ee78b1bbe9c6f3e6df99f3b967132fa12bef77d/packages/edit-site/src/components/header/more-menu/index.js

export function MoreMenu(): JSX.Element {
  return (
    <MoreMenuDropdown
      className="edit-site-more-menu"
      popoverProps={{
        className: 'edit-site-more-menu__content',
      }}
    >
      {() => (
        <>
          <MenuGroup label={_x('View', 'noun', 'mailpoet')}>
            <PreferenceToggleMenuItem
              scope={storeName}
              name="fullscreenMode"
              label={__('Fullscreen mode', 'mailpoet')}
              info={__('Work without distraction', 'mailpoet')}
              messageActivated={__('Fullscreen mode activated', 'mailpoet')}
              messageDeactivated={__('Fullscreen mode deactivated', 'mailpoet')}
              shortcut={displayShortcut.secondary('f')}
            />
          </MenuGroup>
          <MenuGroup>
            <MenuItem
              onClick={() => {
                window.location.href = MailPoet.urls.automationListing;
              }}
            >
              {__('View all automations', 'mailpoet')}
            </MenuItem>
          </MenuGroup>
        </>
      )}
    </MoreMenuDropdown>
  );
}

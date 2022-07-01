import { MenuGroup } from '@wordpress/components';
import { displayShortcut } from '@wordpress/keycodes';
import { __, _x } from '@wordpress/i18n';
import { MoreMenuDropdown } from '@wordpress/interface';
import { PreferenceToggleMenuItem } from '@wordpress/preferences';
import { storeName } from '../../store';

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
        <MenuGroup label={_x('View', 'noun')}>
          <PreferenceToggleMenuItem
            scope={storeName}
            name="fullscreenMode"
            label={__('Fullscreen mode')}
            info={__('Work without distraction')}
            messageActivated={__('Fullscreen mode activated')}
            messageDeactivated={__('Fullscreen mode deactivated')}
            shortcut={displayShortcut.secondary('f')}
          />
        </MenuGroup>
      )}
    </MoreMenuDropdown>
  );
}

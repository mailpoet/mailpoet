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
        <MenuGroup label={_x('View', 'noun', 'mailpoet')}>
          <PreferenceToggleMenuItem
            scope="core"
            name="fixedToolbar"
            label={__('Top toolbar', 'mailpoet')}
            info={__(
              'Access all block and document tools in a single place',
              'mailpoet',
            )}
            messageActivated={__('Top toolbar activated', 'mailpoet')}
            messageDeactivated={__('Top toolbar deactivated', 'mailpoet')}
          />
          <PreferenceToggleMenuItem
            scope="core"
            name="focusMode"
            label={__('Spotlight mode', 'mailpoet')}
            info={__('Focus at one block at a time', 'mailpoet')}
            messageActivated={__('Spotlight mode activated', 'mailpoet')}
            messageDeactivated={__('Spotlight mode deactivated', 'mailpoet')}
          />
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
      )}
    </MoreMenuDropdown>
  );
}

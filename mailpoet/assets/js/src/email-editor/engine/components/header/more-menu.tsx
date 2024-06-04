import { MenuGroup, MenuItem, DropdownMenu } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { displayShortcut } from '@wordpress/keycodes';
import { moreVertical } from '@wordpress/icons';
import { useEntityProp } from '@wordpress/core-data';
import { __, _x } from '@wordpress/i18n';
import { PreferenceToggleMenuItem } from '@wordpress/preferences';
import { useSelect, useDispatch } from '@wordpress/data';
import { storeName } from '../../store';
import { TrashModal } from './trash-modal';

// See:
//   https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/edit-post/src/components/header/more-menu/index.js
//   https://github.com/WordPress/gutenberg/blob/0ee78b1bbe9c6f3e6df99f3b967132fa12bef77d/packages/edit-site/src/components/header/more-menu/index.js

export function MoreMenu(): JSX.Element {
  const [showTrashModal, setShowTrashModal] = useState(false);
  const { urls, postId } = useSelect(
    (select) => ({
      urls: select(storeName).getUrls(),
      postId: select(storeName).getEmailPostId(),
    }),
    [],
  );
  const [status, setStatus] = useEntityProp(
    'postType',
    'mailpoet_email',
    'status',
  );
  const { saveEditedEmail, updateEmailMailPoetProperty } =
    useDispatch(storeName);
  const goToListings = () => {
    window.location.href = urls.listings;
  };

  return (
    <>
      <DropdownMenu
        className="edit-site-more-menu"
        popoverProps={{
          className: 'edit-site-more-menu__content',
        }}
        icon={moreVertical}
        label={__('More', 'mailpoet')}
      >
        {() => (
          <>
            <MenuGroup label={_x('View', 'noun', 'mailpoet')}>
              <PreferenceToggleMenuItem
                scope={storeName}
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
                scope={storeName}
                name="focusMode"
                label={__('Spotlight mode', 'mailpoet')}
                info={__('Focus at one block at a time', 'mailpoet')}
                messageActivated={__('Spotlight mode activated', 'mailpoet')}
                messageDeactivated={__(
                  'Spotlight mode deactivated',
                  'mailpoet',
                )}
              />
              <PreferenceToggleMenuItem
                scope={storeName}
                name="fullscreenMode"
                label={__('Fullscreen mode', 'mailpoet')}
                info={__('Work without distraction', 'mailpoet')}
                messageActivated={__('Fullscreen mode activated', 'mailpoet')}
                messageDeactivated={__(
                  'Fullscreen mode deactivated',
                  'mailpoet',
                )}
                shortcut={displayShortcut.secondary('f')}
              />
            </MenuGroup>
            <MenuGroup>
              {status === 'trash' ? (
                <MenuItem
                  onClick={async () => {
                    await setStatus('draft');
                    await updateEmailMailPoetProperty('deleted_at', '');
                    await saveEditedEmail();
                  }}
                >
                  {__('Restore from trash', 'mailpoet')}
                </MenuItem>
              ) : (
                <MenuItem onClick={() => setShowTrashModal(true)} isDestructive>
                  {__('Move to trash', 'mailpoet')}
                </MenuItem>
              )}
            </MenuGroup>
          </>
        )}
      </DropdownMenu>
      {showTrashModal && (
        <TrashModal
          onClose={() => setShowTrashModal(false)}
          onRemove={goToListings}
          postId={postId}
        />
      )}
    </>
  );
}

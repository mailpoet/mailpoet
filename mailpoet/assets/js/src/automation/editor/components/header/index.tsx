import { Button, Icon, NavigableMenu } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { wordpress } from '@wordpress/icons';
import { PinnedItems } from '@wordpress/interface';
import { InserterToggle } from './inserter_toggle';
import { MoreMenu } from './more_menu';
import { store, storeName } from '../../store';

// See:
//   https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/edit-post/src/components/header/index.js
//   https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/edit-navigation/src/components/header/index.js

export function Header(): JSX.Element {
  const { isFullscreenActive } = useSelect(
    (select) => ({
      isFullscreenActive: select(store).isFeatureActive('fullscreenMode'),
    }),
    [],
  );

  const { activate } = useDispatch(store);

  return (
    <div className="edit-post-header">
      {isFullscreenActive && (
        <Button
          className="edit-post-fullscreen-mode-close has-icon"
          href="admin.php?page=mailpoet-automation"
        >
          <Icon size={36} icon={wordpress} />
        </Button>
      )}
      <div className="edit-post-header__toolbar">
        <NavigableMenu
          className="edit-post-header-toolbar"
          orientation="horizontal"
          role="toolbar"
        >
          <div className="edit-post-header-toolbar__left">
            <InserterToggle />
          </div>
        </NavigableMenu>
      </div>
      <div className="edit-post-header__settings">
        <Button isTertiary>Save Draft</Button>
        <Button
          isPrimary
          className="editor-post-publish-button"
          onClick={activate}
        >
          Activate
        </Button>
        <PinnedItems.Slot scope={storeName} />
        <MoreMenu />
      </div>
    </div>
  );
}

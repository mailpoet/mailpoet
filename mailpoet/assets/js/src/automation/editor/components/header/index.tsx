import { Button, NavigableMenu } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { PinnedItems } from '@wordpress/interface';
import { InserterToggle } from './inserter_toggle';
import { MoreMenu } from './more_menu';
import { store, storeName } from '../../store';

// See:
//   https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/edit-post/src/components/header/index.js
//   https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/edit-navigation/src/components/header/index.js

export function Header(): JSX.Element {
  const { activate } = useDispatch(store);

  return (
    <div className="edit-site-header">
      <div className="edit-site-header_start">
        <NavigableMenu
          className="edit-site-header__toolbar"
          orientation="horizontal"
          role="toolbar"
        >
          <InserterToggle />
        </NavigableMenu>
      </div>

      <div className="edit-site-header_end">
        <div className="edit-site-header__actions">
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
    </div>
  );
}

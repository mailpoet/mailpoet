import { Button, ToolbarItem } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { __, _x } from '@wordpress/i18n';
import { plus } from '@wordpress/icons';
import { storeName } from '../../store';

// See:
//   https://github.com/WordPress/gutenberg/blob/5caeae34b3fb303761e3b9432311b26f4e5ea3a6/packages/edit-post/src/components/header/header-toolbar/index.js
//   https://github.com/WordPress/gutenberg/blob/0ee78b1bbe9c6f3e6df99f3b967132fa12bef77d/packages/edit-site/src/components/header/index.js

export function InserterToggle(): JSX.Element {
  const { isInserterOpened, showIconLabels } = useSelect(
    (select) => ({
      isInserterOpened: select(storeName).isInserterSidebarOpened(),
      showIconLabels: select(storeName).isFeatureActive('showIconLabels'),
    }),
    [],
  );

  const { toggleInserterSidebar } = useDispatch(storeName);

  return (
    <ToolbarItem
      as={Button}
      className="edit-site-header-toolbar__inserter-toggle"
      variant="primary"
      isPressed={isInserterOpened}
      onMouseDown={(event) => event.preventDefault()}
      onClick={toggleInserterSidebar}
      icon={plus}
      label={_x(
        'Toggle step inserter',
        'Generic label for step inserter button',
      )}
      showTooltip={!showIconLabels}
    >
      {showIconLabels && (!isInserterOpened ? __('Add') : __('Close'))}
    </ToolbarItem>
  );
}

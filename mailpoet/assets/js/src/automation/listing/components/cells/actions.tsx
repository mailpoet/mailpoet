import { Fragment } from 'react';
import { __ } from '@wordpress/i18n';
import { DropdownMenu } from '@wordpress/components';
import { moreVertical } from '@wordpress/icons';
import { useDeleteButton, useRestoreButton, useTrashButton } from '../menu';
import { Automation } from '../../automation';
import { EditAutomation } from '../actions';

type Props = {
  automation: Automation;
};

export function Actions({ automation }: Props): JSX.Element {
  // Menu items are using custom hooks because the "DropdownMenu" component uses the "controls"
  // attribute rather than child components, but we need to render modal confirmation dialogs.
  const trash = useTrashButton(automation);
  const restore = useRestoreButton(automation);
  const del = useDeleteButton(automation);

  const menuItems = [trash, restore, del].filter((item) => item);

  return (
    <div className="mailpoet-automation-listing-cell-actions">
      <EditAutomation automation={automation} />
      {menuItems.map(({ control, slot }) => (
        <Fragment key={control.title}>{slot}</Fragment>
      ))}
      <DropdownMenu
        className="mailpoet-automation-listing-more-button"
        label={__('More', 'mailpoet')}
        icon={moreVertical}
        controls={menuItems.map(({ control }) => control)}
        popoverProps={{ position: 'bottom left' }}
      />
    </div>
  );
}

import { Fragment } from 'react';
import { __ } from '@wordpress/i18n';
import { DropdownMenu } from '@wordpress/components';
import { moreVertical } from '@wordpress/icons';
import {
  useDeleteButton,
  useDuplicateButton,
  useRestoreButton,
  useTrashButton,
} from '../menu';
import { Workflow } from '../../workflow';
import { EditWorkflow } from '../actions';

type Props = {
  workflow: Workflow;
};

export function Actions({ workflow }: Props): JSX.Element {
  // Menu items are using custom hooks because the "DropdownMenu" component uses the "controls"
  // attribute rather than child components, but we need to render modal confirmation dialogs.
  const duplicate = useDuplicateButton(workflow);
  const trash = useTrashButton(workflow);
  const restore = useRestoreButton(workflow);
  const del = useDeleteButton(workflow);

  const menuItems = [duplicate, trash, restore, del].filter((item) => item);

  return (
    <div className="mailpoet-automation-listing-cell-actions">
      <EditWorkflow workflow={workflow} />
      {menuItems.map(({ control, slot }) => (
        <Fragment key={control.title}>{slot}</Fragment>
      ))}
      <DropdownMenu
        className="mailpoet-automation-listing-more-button"
        label={__('More', 'mailpoet')}
        icon={moreVertical}
        controls={menuItems.map(({ control }) => control)}
      />
    </div>
  );
}

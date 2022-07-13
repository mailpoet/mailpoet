import { Button, NavigableMenu, TextControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { PinnedItems } from '@wordpress/interface';
import { __ } from '@wordpress/i18n';
import { DocumentActions } from './document_actions';
import { InserterToggle } from './inserter_toggle';
import { MoreMenu } from './more_menu';
import { store, storeName } from '../../store';
import { WorkflowStatus } from '../../../listing/workflow';

// See:
//   https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/edit-post/src/components/header/index.js
//   https://github.com/WordPress/gutenberg/blob/0ee78b1bbe9c6f3e6df99f3b967132fa12bef77d/packages/edit-site/src/components/header/index.js

function ActivateButton(): JSX.Element {
  const { activate } = useDispatch(store);

  return (
    <Button isPrimary className="editor-post-publish-button" onClick={activate}>
      Activate
    </Button>
  );
}

function UpdateButton(): JSX.Element {
  const { activate } = useDispatch(store);

  return (
    <Button isPrimary className="editor-post-publish-button" onClick={activate}>
      Update
    </Button>
  );
}

export function Header(): JSX.Element {
  const { setWorkflowName } = useDispatch(store);
  const { workflowName, workflowStatus } = useSelect(
    (select) => ({
      workflowName: select(store).getWorkflowData().name,
      workflowStatus: select(store).getWorkflowData().status,
    }),
    [],
  );

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

      <div className="edit-site-header_center">
        <DocumentActions>
          {() => (
            <div className="mailpoet-automation-editor-dropdown-name-edit">
              <div className="mailpoet-automation-editor-dropdown-name-edit-title">
                {__('Automation name')}
              </div>
              <TextControl
                value={workflowName}
                onChange={(newName) => setWorkflowName(newName)}
                help={__(
                  `Give the automation a name that indicates its purpose. E.g. "Abandoned cart recovery"`,
                )}
              />
            </div>
          )}
        </DocumentActions>
      </div>

      <div className="edit-site-header_end">
        <div className="edit-site-header__actions">
          <Button isTertiary>{__('Save Draft')}</Button>
          {workflowStatus !== WorkflowStatus.ACTIVE && <ActivateButton />}
          {workflowStatus === WorkflowStatus.ACTIVE && <UpdateButton />}
          <PinnedItems.Slot scope={storeName} />
          <MoreMenu />
        </div>
      </div>
    </div>
  );
}

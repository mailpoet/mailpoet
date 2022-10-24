import { useState } from 'react';
import { Button, NavigableMenu, TextControl } from '@wordpress/components';
import { dispatch, useDispatch, useSelect } from '@wordpress/data';
import { PinnedItems } from '@wordpress/interface';
import { __ } from '@wordpress/i18n';
import { DocumentActions } from './document_actions';
import { Errors } from './errors';
import { InserterToggle } from './inserter_toggle';
import { MoreMenu } from './more_menu';
import { storeName } from '../../store';
import { WorkflowStatus } from '../../../listing/workflow';
import { DeactivateModal } from '../modals/deactivate-modal';

// See:
//   https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/edit-post/src/components/header/index.js
//   https://github.com/WordPress/gutenberg/blob/0ee78b1bbe9c6f3e6df99f3b967132fa12bef77d/packages/edit-site/src/components/header/index.js

function ActivateButton({ onClick }): JSX.Element {
  const { errors } = useSelect(
    (select) => ({
      errors: select(storeName).getErrors(),
    }),
    [],
  );

  return (
    <Button
      variant="primary"
      className="editor-post-publish-button"
      onClick={onClick}
      disabled={!!errors}
    >
      {__('Activate', 'mailpoet')}
    </Button>
  );
}

function UpdateButton(): JSX.Element {
  const { save } = useDispatch(storeName);

  return (
    <Button
      variant="primary"
      className="editor-post-publish-button"
      onClick={save}
    >
      {__('Update', 'mailpoet')}
    </Button>
  );
}

function SaveDraftButton(): JSX.Element {
  const { save } = useDispatch(storeName);

  return (
    <Button variant="tertiary" onClick={save}>
      {__('Save draft', 'mailpoet')}
    </Button>
  );
}

function DeactivateButton(): JSX.Element {
  const [showDeactivateModal, setShowDeactivateModal] = useState(false);
  const [isBusy, setIsBusy] = useState(false);
  const { hasUsersInProgress } = useSelect(
    (select) => ({
      hasUsersInProgress:
        select(storeName).getWorkflowData().stats.totals.in_progress > 0,
    }),
    [],
  );

  const toggleModal = () => {
    setShowDeactivateModal(!showDeactivateModal);
  };
  const deactivateOrShowModal = () => {
    if (hasUsersInProgress) {
      toggleModal();
      return;
    }
    setIsBusy(true);
    void dispatch(storeName).deactivate();
  };

  return (
    <>
      {showDeactivateModal && <DeactivateModal onClose={toggleModal} />}
      <Button
        isBusy={isBusy}
        variant="tertiary"
        onClick={deactivateOrShowModal}
      >
        {__('Deactivate', 'mailpoet')}
      </Button>
    </>
  );
}

type Props = {
  showInserterToggle: boolean;
  toggleActivatePanel: () => void;
};

export function Header({
  showInserterToggle,
  toggleActivatePanel,
}: Props): JSX.Element {
  const { setWorkflowName } = useDispatch(storeName);
  const { workflowName, workflowStatus } = useSelect(
    (select) => ({
      workflowName: select(storeName).getWorkflowData().name,
      workflowStatus: select(storeName).getWorkflowData().status,
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
          {showInserterToggle && <InserterToggle />}
        </NavigableMenu>
      </div>

      <div className="edit-site-header_center">
        <DocumentActions>
          {() => (
            <div className="mailpoet-automation-editor-dropdown-name-edit">
              <div className="mailpoet-automation-editor-dropdown-name-edit-title">
                {__('Automation name', 'mailpoet')}
              </div>
              <TextControl
                value={workflowName}
                onChange={(newName) => setWorkflowName(newName)}
                help={__(
                  `Give the automation a name that indicates its purpose. E.g. "Abandoned cart recovery"`,
                  'mailpoet',
                )}
              />
            </div>
          )}
        </DocumentActions>
      </div>

      <div className="edit-site-header_end">
        <div className="edit-site-header__actions">
          <Errors />
          {workflowStatus !== WorkflowStatus.ACTIVE && (
            <>
              <SaveDraftButton />
              <ActivateButton onClick={toggleActivatePanel} />
            </>
          )}
          {workflowStatus === WorkflowStatus.ACTIVE && (
            <>
              <DeactivateButton />
              <UpdateButton />
            </>
          )}
          <PinnedItems.Slot scope={storeName} />
          <MoreMenu />
        </div>
      </div>
    </div>
  );
}

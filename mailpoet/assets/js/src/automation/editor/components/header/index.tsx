import { useMemo, useState } from 'react';
import classnames from 'classnames';
import {
  Button,
  NavigableMenu,
  TextControl,
  Tooltip,
} from '@wordpress/components';
import { dispatch, useDispatch, useSelect } from '@wordpress/data';
import { Icon, check, cloud } from '@wordpress/icons';
import { PinnedItems } from '@wordpress/interface';
import { __ } from '@wordpress/i18n';
import { displayShortcut } from '@wordpress/keycodes';
import { ErrorBoundary } from 'common';
import { DocumentActions } from './document-actions';
import { Errors } from './errors';
import { InserterToggle } from './inserter-toggle';
import { MoreMenu } from './more-menu';
import { storeName } from '../../store';
import { AutomationStatus } from '../../../listing/automation';
import {
  DeactivateImmediatelyModal,
  DeactivateModal,
} from '../modals/deactivate-modal';

// See:
//   https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/edit-post/src/components/header/index.js
//   https://github.com/WordPress/gutenberg/blob/0ee78b1bbe9c6f3e6df99f3b967132fa12bef77d/packages/edit-site/src/components/header/index.js

export function ActivateButton({ label }): JSX.Element {
  const { errors, isDeactivating } = useSelect(
    (select) => ({
      errors: select(storeName).getErrors(),
      isDeactivating:
        select(storeName).getAutomationData().status ===
        AutomationStatus.DEACTIVATING,
    }),
    [],
  );
  const { openActivationPanel } = useDispatch(storeName);

  const button = (
    <Button
      variant="primary"
      className="editor-post-publish-button"
      onClick={openActivationPanel}
      disabled={isDeactivating || !!errors}
    >
      {label}
    </Button>
  );

  if (isDeactivating) {
    return (
      <Tooltip
        delay={0}
        text={__(
          'Editing an active automation is temporarily unavailable. We are working on introducing this functionality.',
          'mailpoet',
        )}
      >
        {button}
      </Tooltip>
    );
  }

  return button;
}

export function UpdateButton(): JSX.Element {
  const { save } = useDispatch(storeName);

  const { automation, savedState } = useSelect(
    (select) => ({
      automation: select(storeName).getAutomationData(),
      savedState: select(storeName).getSavedState(),
    }),
    [],
  );

  const isDisabled = savedState === 'saving' || savedState === 'saved';

  const label =
    savedState === 'saving'
      ? __('Updating…', 'mailpoet')
      : __('Update', 'mailpoet');

  if (automation.stats.totals.in_progress === 0) {
    return (
      <Button
        variant="primary"
        className="editor-post-publish-button"
        label={label}
        showTooltip
        shortcut={isDisabled ? undefined : displayShortcut.primary('s')}
        isBusy={savedState === 'saving'}
        disabled={isDisabled}
        aria-disabled={isDisabled}
        onClick={save}
      >
        {label}
      </Button>
    );
  }
  return (
    <Tooltip
      delay={0}
      text={__(
        'Editing an active automation is temporarily unavailable. We are working on introducing this functionality.',
        'mailpoet',
      )}
    >
      <Button
        variant="primary"
        className="editor-post-publish-button"
        onClick={save}
        disabled
      >
        {__('Update', 'mailpoet')}
      </Button>
    </Tooltip>
  );
}

function SaveDraftButton(): JSX.Element {
  const savedState = useSelect(
    (select) => select(storeName).getSavedState(),
    [],
  );
  const { save } = useDispatch(storeName);

  const label = useMemo(() => {
    if (savedState === 'saving') {
      return __('Saving', 'mailpoet');
    }
    if (savedState === 'saved') {
      return __('Saved', 'mailpoet');
    }
    return __('Save draft', 'mailpoet');
  }, [savedState]);

  const isDisabled = savedState === 'saving' || savedState === 'saved';

  // use single Button instance for all states so that focus is not lost
  return (
    <Button
      className={classnames([
        'mailpoet-automation-editor-saved-state',
        `is-${savedState}`,
        {
          'components-animate__loading': savedState === 'saving',
        },
      ])}
      variant="tertiary"
      label={label}
      shortcut={isDisabled ? undefined : displayShortcut.primary('s')}
      showTooltip
      disabled={isDisabled}
      aria-disabled={isDisabled}
      onClick={save}
    >
      {savedState === 'saving' && <Icon icon={cloud} />}
      {savedState === 'saved' && <Icon icon={check} />}
      {label}
    </Button>
  );
}

export function DeactivateButton(): JSX.Element {
  const [showDeactivateModal, setShowDeactivateModal] = useState(false);
  const [isBusy, setIsBusy] = useState(false);
  const { hasUsersInProgress } = useSelect(
    (select) => ({
      hasUsersInProgress:
        select(storeName).getAutomationData().stats.totals.in_progress > 0,
    }),
    [],
  );

  const deactivateOrShowModal = () => {
    if (hasUsersInProgress) {
      setShowDeactivateModal(true);
      return;
    }
    setIsBusy(true);
    void dispatch(storeName).deactivate();
  };

  return (
    <>
      {showDeactivateModal && (
        <DeactivateModal
          onClose={() => {
            setShowDeactivateModal(false);
          }}
        />
      )}
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

export function DeactivateNowButton(): JSX.Element {
  const [showDeactivateModal, setShowDeactivateModal] = useState(false);
  const [isBusy, setIsBusy] = useState(false);
  const { hasUsersInProgress } = useSelect(
    (select) => ({
      hasUsersInProgress:
        select(storeName).getAutomationData().stats.totals.in_progress > 0,
    }),
    [],
  );

  const deactivateOrShowModal = () => {
    if (hasUsersInProgress) {
      setShowDeactivateModal(true);
      return;
    }
    setIsBusy(true);
    void dispatch(storeName).deactivate();
  };

  return (
    <>
      {showDeactivateModal && (
        <DeactivateImmediatelyModal
          onClose={() => {
            setShowDeactivateModal(false);
          }}
        />
      )}
      <Button
        isBusy={isBusy}
        variant="tertiary"
        onClick={deactivateOrShowModal}
      >
        {__('Deactivate now', 'mailpoet')}
      </Button>
    </>
  );
}

type Props = {
  showInserterToggle: boolean;
};

export function Header({ showInserterToggle }: Props): JSX.Element {
  const { setAutomationName } = useDispatch(storeName);
  const { automationName, automationStatus } = useSelect(
    (select) => ({
      automationName: select(storeName).getAutomationData().name,
      automationStatus: select(storeName).getAutomationData().status,
    }),
    [],
  );

  return (
    <div className="edit-site-header-edit-mode">
      <div className="edit-site-header-edit-mode_start">
        <NavigableMenu
          className="edit-site-header-edit-mode__toolbar"
          orientation="horizontal"
          role="toolbar"
        >
          {showInserterToggle && <InserterToggle />}
        </NavigableMenu>
      </div>

      <div className="edit-site-header-edit-mode_center">
        <ErrorBoundary>
          <DocumentActions>
            {() => (
              <div className="mailpoet-automation-editor-dropdown-name-edit">
                <div className="mailpoet-automation-editor-dropdown-name-edit-title">
                  {__('Automation name', 'mailpoet')}
                </div>
                <TextControl
                  value={automationName}
                  onChange={(newName) => setAutomationName(newName)}
                  help={__(
                    `Give the automation a name that indicates its purpose. E.g. "Abandoned cart recovery"`,
                    'mailpoet',
                  )}
                />
              </div>
            )}
          </DocumentActions>
        </ErrorBoundary>
      </div>

      <div className="edit-site-header-edit-mode_end">
        <div className="edit-site-header-edit-mode__actions">
          <ErrorBoundary>
            <Errors />
          </ErrorBoundary>
          {automationStatus === AutomationStatus.DRAFT && (
            <>
              <SaveDraftButton />
              <ActivateButton label={__('Activate', 'mailpoet')} />
            </>
          )}
          {automationStatus === AutomationStatus.ACTIVE && (
            <>
              <DeactivateButton />
              <UpdateButton />
            </>
          )}
          {automationStatus === AutomationStatus.DEACTIVATING && (
            <>
              <DeactivateNowButton />
              <ActivateButton label={__('Update & Activate', 'mailpoet')} />
            </>
          )}
          <PinnedItems.Slot scope={storeName} />
          <MoreMenu />
        </div>
      </div>
    </div>
  );
}

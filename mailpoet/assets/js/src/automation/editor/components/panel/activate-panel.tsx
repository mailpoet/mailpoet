import { useEffect, useState } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';
import { Button, Spinner } from '@wordpress/components';
import { closeSmall } from '@wordpress/icons';
import { __, sprintf } from '@wordpress/i18n';
import { storeName } from '../../store';
import { AutomationStatus } from '../../../listing/automation';
import { MailPoet } from '../../../../mailpoet';

function PreStep({ onClose }): JSX.Element {
  const [isActivating, setIsActivating] = useState(false);
  const { activate } = useDispatch(storeName);

  return (
    <>
      <div className="mailpoet-automation-activate-panel__header">
        <div className="mailpoet-automation-activate-panel__header-activate-button">
          <Button
            variant="primary"
            disabled={isActivating}
            isBusy={isActivating}
            autoFocus={!isActivating}
            onClick={() => {
              setIsActivating(true);
              activate();
            }}
          >
            {isActivating && __('Activating…', 'mailpoet')}
            {!isActivating && __('Activate', 'mailpoet')}
          </Button>
        </div>

        <div className="mailpoet-automation-activate-panel__header-cancel-button">
          <Button variant="secondary" onClick={onClose} disabled={isActivating}>
            {__('Cancel', 'mailpoet')}
          </Button>
        </div>
      </div>

      {isActivating && (
        <div className="mailpoet-automation-activate-panel__body">
          <Spinner />
        </div>
      )}

      {!isActivating && (
        <div className="mailpoet-automation-activate-panel__body">
          <p>
            <strong>{__('Are you ready to activate?', 'mailpoet')}</strong>
          </p>
          <p>
            {__('Double-check your settings before activating.', 'mailpoet')}
          </p>
        </div>
      )}
    </>
  );
}

function PostStep({ onClose }): JSX.Element {
  const { automation } = useSelect(
    (select) => ({
      automation: select(storeName).getAutomationData(),
    }),
    [],
  );

  const goToListings = () => {
    window.location.href = MailPoet.urls.automationListing;
  };

  return (
    <>
      <div className="mailpoet-automation-activate-panel__header">
        <Button
          icon={closeSmall}
          onClick={onClose}
          label={__('Close', 'mailpoet')}
        />
      </div>

      <div className="mailpoet-automation-activate-panel__body">
        <div className="mailpoet-automation-activate-panel__section">
          {sprintf(__('"%s" is now live.', 'mailpoet'), automation.name)}
        </div>
        <p>
          <strong>{__('What’s next?', 'mailpoet')}</strong>
        </p>
        <p>
          {__(
            'View all your automations to track statistics and create new ones.',
            'mailpoet',
          )}
        </p>
        <Button variant="secondary" onClick={goToListings}>
          {__('View all automations', 'mailpoet')}
        </Button>
      </div>
    </>
  );
}

export function ActivatePanel(): JSX.Element {
  const { automation, errors } = useSelect(
    (select) => ({
      errors: select(storeName).getErrors(),
      automation: select(storeName).getAutomationData(),
    }),
    [],
  );

  const { closeActivationPanel } = useDispatch(storeName);

  useEffect(() => {
    if (errors) {
      closeActivationPanel();
    }
  }, [errors, closeActivationPanel]);

  if (errors) {
    return null;
  }
  const isActive = automation.status === AutomationStatus.ACTIVE;
  return (
    <div className="mailpoet-automation-activate-panel">
      {isActive && <PostStep onClose={closeActivationPanel} />}
      {!isActive && <PreStep onClose={closeActivationPanel} />}
    </div>
  );
}

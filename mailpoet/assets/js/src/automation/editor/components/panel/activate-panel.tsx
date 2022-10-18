import { useDispatch, useSelect } from '@wordpress/data';
import { Button } from '@wordpress/components';
import { closeSmall } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { storeName } from '../../store';
import { WorkflowStatus } from '../../../listing/workflow';
import { MailPoet } from '../../../../mailpoet';

function PreStep({ onClose }): JSX.Element {
  const { activate } = useDispatch(storeName);
  return (
    <>
      <div className="mailpoet-automation-activate-panel__header">
        <div className="mailpoet-automation-activate-panel__header-activate-button">
          <Button variant="primary" onClick={activate}>
            {__('Activate', 'mailpoet')}
          </Button>
        </div>

        <div className="mailpoet-automation-activate-panel__header-cancel-button">
          <Button variant="secondary" onClick={onClose}>
            {__('Cancel', 'mailpoet')}
          </Button>
        </div>
      </div>

      <div className="mailpoet-automation-activate-panel__body">
        <p>
          <strong>{__('Are you ready to activate?', 'mailpoet')}</strong>
        </p>
        <p>{__('Double-check your settings before activating', 'mailpoet')}</p>
      </div>
    </>
  );
}

function PostStep({ onClose }): JSX.Element {
  const { workflow } = useSelect(
    (select) => ({
      workflow: select(storeName).getWorkflowData(),
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
          {__('"%s" is now live.', 'mailpoet').replace('%s', workflow.name)}
        </div>
        <p>
          <strong>{__("What's next?", 'mailpoet')}</strong>
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

export function ActivatePanel({ onClose }): JSX.Element {
  const { workflow, errors } = useSelect(
    (select) => ({
      errors: select(storeName).getErrors(),
      workflow: select(storeName).getWorkflowData(),
    }),
    [],
  );

  if (errors) {
    return null;
  }
  const isActive = workflow.status === WorkflowStatus.ACTIVE;
  return (
    <div className="mailpoet-automation-activate-panel">
      {isActive && <PostStep onClose={onClose} />}
      {!isActive && <PreStep onClose={onClose} />}
    </div>
  );
}

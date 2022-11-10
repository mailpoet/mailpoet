import { useState } from 'react';
import { Button, Modal } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { dispatch, useSelect } from '@wordpress/data';
import { storeName } from '../../store';
import { AutomationStatus } from '../../../listing/automation';

type DeactivateImmediatelyModalProps = {
  onClose: () => void;
};
export function DeactivateImmediatelyModal({
  onClose,
}: DeactivateImmediatelyModalProps): JSX.Element {
  const [isBusy, setIsBusy] = useState<boolean>(false);
  return (
    <Modal
      className="mailpoet-automatoin-deactivate-modal"
      title={__('Stop automatoin for all subscribers?', 'mailpoet')}
      onRequestClose={onClose}
    >
      <p>
        {__(
          'Are you sure you want to deactivate now? This would stop this automation for all subscribers immediately.',
          'mailpoet',
        )}
      </p>

      <Button
        isBusy={isBusy}
        variant="primary"
        onClick={() => {
          setIsBusy(true);
          dispatch(storeName).deactivate(true);
        }}
      >
        {__('Deactivate now', 'mailpoet')}
      </Button>

      <Button disabled={isBusy} variant="tertiary" onClick={onClose}>
        {__('Cancel', 'mailpoet')}
      </Button>
    </Modal>
  );
}

type DeactivateModalProps = {
  onClose: () => void;
};
export function DeactivateModal({
  onClose,
}: DeactivateModalProps): JSX.Element {
  const { automationName } = useSelect(
    (select) => ({
      automationName: select(storeName).getAutomationData().name,
    }),
    [],
  );
  const [selected, setSelected] = useState<
    AutomationStatus.DRAFT | AutomationStatus.DEACTIVATING
  >(AutomationStatus.DEACTIVATING);
  const [isBusy, setIsBusy] = useState<boolean>(false);
  // translators: %s is the name of the automation.
  const title = sprintf(
    __('Deactivate the "%s" automation?', 'mailpoet'),
    automationName,
  );

  return (
    <Modal
      className="mailpoet-automatoin-deactivate-modal"
      title={title}
      onRequestClose={onClose}
    >
      {__(
        "Some subscribers entered but have not finished the flow. Let's decide what to do in this case.",
        'mailpoet',
      )}
      <ul className="mailpoet-automation-options">
        <li>
          <label
            className={
              selected === AutomationStatus.DEACTIVATING
                ? 'mailpoet-automation-option active'
                : 'mailpoet-automation-option'
            }
          >
            <span>
              <input
                type="radio"
                disabled={isBusy}
                name="deactivation-method"
                checked={selected === AutomationStatus.DEACTIVATING}
                onChange={() => setSelected(AutomationStatus.DEACTIVATING)}
              />
            </span>
            <span>
              <strong>
                {__('Let entered subscribers finish the flow', 'mailpoet')}
              </strong>
              {__(
                "New subscribers won't enter, but recently entered could proceed.",
                'mailpoet',
              )}
            </span>
          </label>
        </li>
        <li>
          <label
            className={
              selected === AutomationStatus.DRAFT
                ? 'mailpoet-automation-option active'
                : 'mailpoet-automation-option'
            }
          >
            <span>
              <input
                type="radio"
                disabled={isBusy}
                name="deactivation-method"
                checked={selected === AutomationStatus.DRAFT}
                onChange={() => setSelected(AutomationStatus.DRAFT)}
              />
            </span>
            <span>
              <strong>
                {__('Stop automation for all subscribers', 'mailpoet')}
              </strong>
              {__(
                'Automation will be deactivated for all the subscribers immediately.',
                'mailpoet',
              )}
            </span>
          </label>
        </li>
      </ul>

      <Button
        isBusy={isBusy}
        variant="primary"
        onClick={() => {
          setIsBusy(true);
          dispatch(storeName).deactivate(
            selected !== AutomationStatus.DEACTIVATING,
          );
        }}
      >
        {__('Deactivate automation', 'mailpoet')}
      </Button>

      <Button disabled={isBusy} variant="tertiary" onClick={onClose}>
        {__('Cancel', 'mailpoet')}
      </Button>
    </Modal>
  );
}

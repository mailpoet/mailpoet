import { useEffect, useState } from 'react';
import { Button, Modal } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { dispatch, useSelect } from '@wordpress/data';
import { storeName } from '../../store';
import { AutomationStatus } from '../../../listing/automation';
import { sendTelemetryEvent } from '../../telemetry';

type DeactivateImmediatelyModalProps = {
  onClose: () => void;
  onRequestClose?: () => void;
};
export function DeactivateImmediatelyModal({
  onClose,
  onRequestClose,
}: DeactivateImmediatelyModalProps): JSX.Element {
  const [isBusy, setIsBusy] = useState<boolean>(false);
  const { automationId } = useSelect(
    (s) => ({
      automationId: s(storeName).getAutomationData().id,
    }),
    [],
  );

  useEffect(() => {
    sendTelemetryEvent('modal_view', {
      modal_title: 'deactivate_automation',
      automation_id: automationId,
    });
  }, [automationId]);

  return (
    <Modal
      className="mailpoet-automation-deactivate-modal"
      title={__('Stop automation for all subscribers?', 'mailpoet')}
      onRequestClose={onRequestClose ?? onClose}
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
          sendTelemetryEvent('modal_button_click', {
            modal_title: 'deactivate_automation',
            button_label: 'deactivate_now',
            automation_id: automationId,
          });
          setIsBusy(true);
          void dispatch(storeName).deactivate(true, {
            source: 'modal',
            selected_option: 'stop_all_subscribers',
          });
        }}
      >
        {__('Deactivate now', 'mailpoet')}
      </Button>

      <Button
        disabled={isBusy}
        variant="tertiary"
        onClick={() => {
          sendTelemetryEvent('modal_button_click', {
            modal_title: 'deactivate_automation',
            button_label: 'cancel',
            automation_id: automationId,
          });
          onClose();
        }}
      >
        {__('Cancel', 'mailpoet')}
      </Button>
    </Modal>
  );
}

type DeactivateModalProps = {
  onClose: () => void;
  onRequestClose?: () => void;
};
export function DeactivateModal({
  onClose,
  onRequestClose,
}: DeactivateModalProps): JSX.Element {
  const { automationName, automationId } = useSelect(
    (s) => ({
      automationName: s(storeName).getAutomationData().name,
      automationId: s(storeName).getAutomationData().id,
    }),
    [],
  );
  const [selected, setSelected] = useState<
    AutomationStatus.DRAFT | AutomationStatus.DEACTIVATING
  >(AutomationStatus.DEACTIVATING);
  const [isBusy, setIsBusy] = useState<boolean>(false);

  useEffect(() => {
    sendTelemetryEvent('modal_view', {
      modal_title: 'deactivate_automation',
      automation_id: automationId,
    });
  }, [automationId]);

  // translators: %s is the name of the automation.
  const title = sprintf(
    __('Deactivate the "%s" automation?', 'mailpoet'),
    automationName,
  );

  const selectedOption =
    selected === AutomationStatus.DEACTIVATING
      ? 'let_existing_finish'
      : 'stop_all_subscribers';

  return (
    <Modal
      className="mailpoet-automation-deactivate-modal"
      title={title}
      onRequestClose={onRequestClose ?? onClose}
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
                onChange={() => {
                  sendTelemetryEvent('modal_option_select', {
                    modal_title: 'deactivate_automation',
                    selected_option: 'let_existing_finish',
                    automation_id: automationId,
                  });
                  setSelected(AutomationStatus.DEACTIVATING);
                }}
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
                onChange={() => {
                  sendTelemetryEvent('modal_option_select', {
                    modal_title: 'deactivate_automation',
                    selected_option: 'stop_all_subscribers',
                    automation_id: automationId,
                  });
                  setSelected(AutomationStatus.DRAFT);
                }}
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
          sendTelemetryEvent('modal_button_click', {
            modal_title: 'deactivate_automation',
            button_label: 'deactivate',
            selected_option: selectedOption,
            automation_id: automationId,
          });
          setIsBusy(true);
          void dispatch(storeName).deactivate(
            selected !== AutomationStatus.DEACTIVATING,
            { source: 'modal', selected_option: selectedOption },
          );
        }}
      >
        {__('Deactivate automation', 'mailpoet')}
      </Button>

      <Button
        disabled={isBusy}
        variant="tertiary"
        onClick={() => {
          sendTelemetryEvent('modal_button_click', {
            modal_title: 'deactivate_automation',
            button_label: 'cancel',
            automation_id: automationId,
          });
          onClose();
        }}
      >
        {__('Cancel', 'mailpoet')}
      </Button>
    </Modal>
  );
}

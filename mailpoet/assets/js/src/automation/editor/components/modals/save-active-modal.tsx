import { useEffect, useState } from 'react';
import { Button, Modal } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { dispatch, useSelect } from '@wordpress/data';
import { storeName } from '../../store';
import { sendTelemetryEvent } from '../../telemetry';

const LET_FINISH = 'let_existing_finish';
const STOP_ALL = 'stop_all_subscribers';

type Props = {
  onClose: () => void;
  onRequestClose?: () => void;
};

export function SaveActiveModal({
  onClose,
  onRequestClose,
}: Props): JSX.Element {
  const { automationName, automationId } = useSelect(
    (s) => ({
      automationName: s(storeName).getAutomationData().name,
      automationId: s(storeName).getAutomationData().id,
    }),
    [],
  );
  const [selected, setSelected] = useState<typeof LET_FINISH | typeof STOP_ALL>(
    LET_FINISH,
  );
  const [isBusy, setIsBusy] = useState<boolean>(false);

  useEffect(() => {
    sendTelemetryEvent('modal_view', {
      modal_title: 'save_active_automation',
      automation_id: automationId,
    });
  }, [automationId]);

  // translators: %s is the name of the automation.
  const title = sprintf(
    __('Save changes to the "%s" automation?', 'mailpoet'),
    automationName,
  );

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
              selected === LET_FINISH
                ? 'mailpoet-automation-option active'
                : 'mailpoet-automation-option'
            }
          >
            <span>
              <input
                type="radio"
                disabled={isBusy}
                name="save-method"
                checked={selected === LET_FINISH}
                onChange={() => {
                  sendTelemetryEvent('modal_option_select', {
                    modal_title: 'save_active_automation',
                    selected_option: LET_FINISH,
                    automation_id: automationId,
                  });
                  setSelected(LET_FINISH);
                }}
              />
            </span>
            <span>
              <strong>
                {__('Let entered subscribers finish the flow', 'mailpoet')}
              </strong>
              {__(
                'New subscribers will enter the updated automation, but recently entered could proceed through the previous version.',
                'mailpoet',
              )}
            </span>
          </label>
        </li>
        <li>
          <label
            className={
              selected === STOP_ALL
                ? 'mailpoet-automation-option active'
                : 'mailpoet-automation-option'
            }
          >
            <span>
              <input
                type="radio"
                disabled={isBusy}
                name="save-method"
                checked={selected === STOP_ALL}
                onChange={() => {
                  sendTelemetryEvent('modal_option_select', {
                    modal_title: 'save_active_automation',
                    selected_option: STOP_ALL,
                    automation_id: automationId,
                  });
                  setSelected(STOP_ALL);
                }}
              />
            </span>
            <span>
              <strong>
                {__('Stop automation for entered subscribers', 'mailpoet')}
              </strong>
              {__(
                'Automation will stop for recently entered subscribers immediately. New subscribers will go through the updated automation.',
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
            modal_title: 'save_active_automation',
            button_label: 'save',
            selected_option: selected,
            automation_id: automationId,
          });
          setIsBusy(true);
          void dispatch(storeName).save({
            cancelRunningRuns: selected === STOP_ALL,
          });
          onClose();
        }}
      >
        {__('Save', 'mailpoet')}
      </Button>

      <Button
        disabled={isBusy}
        variant="tertiary"
        onClick={() => {
          sendTelemetryEvent('modal_button_click', {
            modal_title: 'save_active_automation',
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

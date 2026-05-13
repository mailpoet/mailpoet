import { useState } from 'react';
import { Button, Modal } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { dispatch, useSelect } from '@wordpress/data';
import { storeName } from '../../store';

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
  const { automationName } = useSelect(
    (s) => ({
      automationName: s(storeName).getAutomationData().name,
    }),
    [],
  );
  const [selected, setSelected] = useState<typeof LET_FINISH | typeof STOP_ALL>(
    LET_FINISH,
  );
  const [isBusy, setIsBusy] = useState<boolean>(false);

  // translators: %s is the name of the automation.
  const title = sprintf(
    __('Save changes to the "%s" automation?', 'mailpoet'),
    automationName,
  );

  return (
    <Modal
      className="mailpoet-automation-save-active-modal"
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
        onClick={async () => {
          setIsBusy(true);
          try {
            await dispatch(storeName).save({
              cancelRunningRuns: selected === STOP_ALL,
            });
            onClose();
          } catch {
            setIsBusy(false);
          }
        }}
      >
        {__('Save', 'mailpoet')}
      </Button>

      <Button
        disabled={isBusy}
        variant="tertiary"
        onClick={() => {
          onClose();
        }}
      >
        {__('Cancel', 'mailpoet')}
      </Button>
    </Modal>
  );
}

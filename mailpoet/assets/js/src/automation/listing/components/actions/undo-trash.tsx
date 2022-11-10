import { Button } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { storeName } from '../../store/constants';
import { Automation, AutomationStatus } from '../../automation';

type Props = {
  automation: Automation;
  previousStatus: AutomationStatus;
};

export function UndoTrashButton({
  automation,
  previousStatus,
}: Props): JSX.Element {
  const { restoreAutomation } = useDispatch(storeName);

  return (
    <Button
      variant="link"
      onClick={() => restoreAutomation(automation, previousStatus)}
    >
      {__('Undo', 'mailpoet')}
    </Button>
  );
}

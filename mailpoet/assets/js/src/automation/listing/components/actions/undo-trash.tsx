import { Button } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import {
  ReduxStoreConfig,
  StoreDescriptor,
} from '@wordpress/data/build-types/types';
import { __ } from '@wordpress/i18n';
import { State } from 'automation/listing/store/types';
import { storeName } from '../../store/constants';
import { Automation, AutomationStatus } from '../../automation';

// workaround to avoid import cycles
const store = { name: storeName } as StoreDescriptor<
  ReduxStoreConfig<
    State,
    {
      restoreAutomation: (
        automation: Automation,
        status: AutomationStatus,
      ) => null;
    },
    null
  >
>;

type Props = {
  automation: Automation;
  previousStatus: AutomationStatus;
};

export function UndoTrashButton({
  automation,
  previousStatus,
}: Props): JSX.Element {
  const { restoreAutomation } = useDispatch(store);

  return (
    <Button
      variant="link"
      onClick={() => restoreAutomation(automation, previousStatus)}
    >
      {__('Undo', 'mailpoet')}
    </Button>
  );
}

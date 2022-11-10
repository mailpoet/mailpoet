import { dispatch } from '@wordpress/data';
import { AddStepButton } from './add-step-button';
import { storeName } from '../../store';

type Props = {
  previousStepId: string;
};

export function Separator({ previousStepId }: Props): JSX.Element {
  const { setInserterPopover } = dispatch(storeName);

  return (
    <div className="mailpoet-automation-editor-separator">
      <AddStepButton
        onClick={(button) =>
          setInserterPopover({ anchor: button, type: 'steps' })
        }
        previousStepId={previousStepId}
      />
    </div>
  );
}

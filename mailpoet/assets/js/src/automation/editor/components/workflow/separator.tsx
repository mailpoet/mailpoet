import { dispatch } from '@wordpress/data';
import { AddStepButton } from './add-step-button';
import { store } from '../../store';

export function Separator(): JSX.Element {
  const { setInserterPopover } = dispatch(store);

  return (
    <div className="mailpoet-automation-editor-separator">
      <AddStepButton
        onClick={(button) =>
          setInserterPopover({ anchor: button, type: 'steps' })
        }
      />
    </div>
  );
}

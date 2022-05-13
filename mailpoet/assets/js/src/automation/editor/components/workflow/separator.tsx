import { dispatch } from '@wordpress/data';
import { AddStepButton } from './add-step-button';
import { store } from '../../store';

export function Separator(): JSX.Element {
  const { setInserterPopoverAnchor } = dispatch(store);

  return (
    <div className="mailpoet-automation-editor-separator">
      <AddStepButton onClick={(button) => setInserterPopoverAnchor(button)} />
    </div>
  );
}

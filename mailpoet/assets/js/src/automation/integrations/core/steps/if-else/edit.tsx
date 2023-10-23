import { useDispatch, useSelect } from '@wordpress/data';
import { strings } from './strings';
import { FiltersPanel } from '../../../../editor/components/filters';
import { storeName } from '../../../../editor/store';

export function Edit(): JSX.Element {
  const selectedStep = useSelect(
    (select) => select(storeName).getSelectedStep(),
    [],
  );
  const { removeStepErrors } = useDispatch(storeName);
  return (
    <FiltersPanel
      strings={strings}
      onChange={() => void removeStepErrors(selectedStep.id)}
    />
  );
}

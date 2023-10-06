import { strings } from './strings';
import { FiltersPanel } from '../../../../editor/components/filters';

export function Edit(): JSX.Element {
  return <FiltersPanel strings={strings} />;
}

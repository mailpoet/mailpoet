import { strings } from './strings';
import { Step } from '../../../../editor/components/automation/types';
import { FiltersChip } from '../../../../editor/components/filters';

type Props = {
  step: Step;
};

export function Footer({ step }: Props): JSX.Element {
  return <FiltersChip step={step} strings={strings} />;
}

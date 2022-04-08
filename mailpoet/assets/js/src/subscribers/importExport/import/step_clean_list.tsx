import { RouteComponentProps } from 'react-router-dom';
import CleanList from './clean_list';

export default function StepCleanList({
  history,
}: RouteComponentProps): JSX.Element {
  return (
    <CleanList onProceed={(): void => history.push('step_method_selection')} />
  );
}

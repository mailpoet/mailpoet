import { RouteComponentProps } from 'react-router-dom';
import { CleanList } from './clean_list';

function StepCleanList({ history }: RouteComponentProps): JSX.Element {
  return (
    <CleanList onProceed={(): void => history.push('step_method_selection')} />
  );
}

StepCleanList.displayName = 'StepCleanList';
export { StepCleanList };

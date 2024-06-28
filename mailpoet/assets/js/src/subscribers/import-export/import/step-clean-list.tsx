import { useNavigate } from 'react-router-dom';
import { CleanList } from './clean-list';

function StepCleanList(): JSX.Element {
  const navigate = useNavigate();
  return (
    <CleanList onProceed={(): void => navigate('/step_method_selection')} />
  );
}

StepCleanList.displayName = 'StepCleanList';
export { StepCleanList };

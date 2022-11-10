import { EditAutomation } from '../actions';
import { Automation } from '../../automation';

type Props = {
  automation: Automation;
};

export function Name({ automation }: Props): JSX.Element {
  return <EditAutomation automation={automation} label={automation.name} />;
}

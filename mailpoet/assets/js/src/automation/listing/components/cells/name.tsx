import { EditAutomation } from '../actions';
import { AutomationItem } from '../../store';

type Props = {
  automation: AutomationItem;
};

export function Name({ automation }: Props): JSX.Element {
  return (
    <>
      <EditAutomation automation={automation} label={automation.name} />
      {automation.description && <div>{automation.description}</div>}
    </>
  );
}

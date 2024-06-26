import { Automation } from './automation';
import { Actions, Name, Subscribers } from './components/cells';
import { AutomationStatus } from '../components/status';

export function getRow(automation: Automation): object[] {
  return [
    {
      id: automation.id,
      value: automation.name,
      display: <Name automation={automation} />,
    },
    {
      id: automation.id,
      value: null,
      display: <Subscribers automation={automation} />,
    },
    {
      id: automation.id,
      value: automation.status,
      display: <AutomationStatus status={automation.status} />,
    },
    {
      id: automation.id,
      value: null,
      display: <Actions automation={automation} />,
    },
  ];
}

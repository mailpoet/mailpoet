import { Automation } from './automation';
import { Actions, Name, Status, Subscribers } from './components/cells';

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
      display: <Status automation={automation} />,
    },
    {
      id: automation.id,
      value: null,
      display: <Actions automation={automation} />,
    },
  ];
}

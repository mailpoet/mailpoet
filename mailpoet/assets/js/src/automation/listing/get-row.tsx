import { Workflow } from './workflow';
import { Edit, More, Name, Status, Subscribers } from './cells';

export function getRow(workflow: Workflow): object[] {
  return [
    {
      value: workflow.name,
      display: <Name workflow={workflow} />,
    },
    {
      value: null,
      display: <Subscribers workflow={workflow} />,
    },
    {
      value: workflow.status,
      display: <Status workflow={workflow} />,
    },
    {
      value: null,
      display: <Edit workflow={workflow} />,
    },
    {
      value: null,
      display: <More workflow={workflow} />,
    },
  ];
}

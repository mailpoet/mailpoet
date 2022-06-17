import { Workflow } from './workflow';
import { Subscribers } from './cells/Subscribers';
import { Status } from './cells/Status';
import { Name } from './cells/Name';
import { Edit } from './cells/Edit';
import { More } from './cells/More';

export function Row(workflow: Workflow): object[] {
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

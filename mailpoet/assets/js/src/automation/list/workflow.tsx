import PropTypes from 'prop-types';

export enum WorkflowStatus {
  ACTIVE = 'active',
  INACTIVE = 'inactive',
}

export type Workflow = {
  id: number;
  name: string;
  status: WorkflowStatus;
};

export const WorkflowPropsShape = {
  id: PropTypes.number.isRequired,
  name: PropTypes.string.isRequired,
  status: PropTypes.string.isRequired,
};

export interface WorkflowProps {
  workflow: Workflow;
}

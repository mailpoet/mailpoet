export enum WorkflowStatus {
  ACTIVE = 'active',
  INACTIVE = 'inactive',
}

export type Workflow = {
  id: number;
  name: string;
  status: WorkflowStatus;
};

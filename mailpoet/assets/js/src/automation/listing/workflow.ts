export enum WorkflowStatus {
  ACTIVE = 'active',
  INACTIVE = 'inactive',
  DRAFT = 'draft',
}

export type Workflow = {
  id: number;
  name: string;
  status: WorkflowStatus;
};

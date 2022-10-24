export enum WorkflowStatus {
  ACTIVE = 'active',
  INACTIVE = 'inactive',
  DRAFT = 'draft',
  TRASH = 'trash',
  // @ToDo: Needs to be aligned with MAILPOET-4731
  DEACTIVATING = 'deactivating',
}

export type Workflow = {
  id: number;
  name: string;
  status: WorkflowStatus;
  stats: {
    totals: {
      entered: number;
      in_progress: number;
      exited: number;
    };
  };
};

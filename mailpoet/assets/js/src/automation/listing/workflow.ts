export enum WorkflowStatus {
  ACTIVE = 'active',
  INACTIVE = 'inactive',
  DRAFT = 'draft',
  TRASH = 'trash',
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

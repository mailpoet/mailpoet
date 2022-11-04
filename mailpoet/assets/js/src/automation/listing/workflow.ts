export enum WorkflowStatus {
  ACTIVE = 'active',
  DRAFT = 'draft',
  TRASH = 'trash',
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

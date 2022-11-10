export enum AutomationStatus {
  ACTIVE = 'active',
  DRAFT = 'draft',
  TRASH = 'trash',
  DEACTIVATING = 'deactivating',
}

export type Automation = {
  id: number;
  name: string;
  status: AutomationStatus;
  stats: {
    totals: {
      entered: number;
      in_progress: number;
      exited: number;
    };
  };
};

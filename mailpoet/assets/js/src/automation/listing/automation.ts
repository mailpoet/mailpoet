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
  created_at?: string;
  updated_at?: string;
  stats: {
    totals: {
      entered: number;
      in_progress: number;
      exited: number;
    };
  };
};

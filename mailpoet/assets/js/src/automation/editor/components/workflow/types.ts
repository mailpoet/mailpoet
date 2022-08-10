export type Step = {
  id: string;
  type: 'trigger' | 'action';
  key: string;
  next_step_id?: string;
  args: Record<string, unknown>;
};

export type Workflow = {
  id?: number;
  name: string;
  status: 'active' | 'inactive' | 'draft' | 'trash';
  created_at: string;
  updated_at: string;
  activated_at: string;
  author: {
    id: number;
    name: string;
  };
  steps: Record<string, Step>;
};

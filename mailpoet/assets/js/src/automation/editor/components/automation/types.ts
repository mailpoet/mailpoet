import { AutomationStatus } from '../../../listing/automation';

export type NextStep = {
  id: string;
};

export type Step = {
  id: string;
  type: 'root' | 'trigger' | 'action';
  key: string;
  args: Record<string, unknown>;
  next_steps: NextStep[];
};

export type Automation = {
  id: number;
  name: string;
  status: AutomationStatus;
  created_at: string;
  updated_at: string;
  activated_at: string;
  author: {
    id: number;
    name: string;
  };
  stats: {
    has_values: boolean;
    totals: {
      entered: number;
      in_progress: number;
      exited: number;
    };
  };
  steps: Record<string, Step> & { root: Step };
};

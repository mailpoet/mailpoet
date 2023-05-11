import { AutomationStatus } from '../../../../listing/automation';
import { Step } from '../../../../editor/components/automation/types';

type Automation = {
  id: number;
  name: string;
  status: AutomationStatus;
  steps: Record<string, Step>;
};

type CurrentAndPrevious = {
  current: number;
  previous: number;
};

type OverviewSectionData = SectionData & {
  opened: CurrentAndPrevious;
  clicked: CurrentAndPrevious;
  orders: CurrentAndPrevious;
  revenue: CurrentAndPrevious;
  revenue_formatted: CurrentAndPrevious;
  total: CurrentAndPrevious;
};

export type SectionData = Record<string, unknown>;

export type Section = {
  id: string;
  name: string;
  endpoint: string;
  data: undefined | SectionData;
};

export type OverviewSection = Section & {
  data: undefined | OverviewSectionData;
};

export type Query = {
  compare: string;
  period: string;
  after: string | undefined;
  before: string | undefined;
};
export type State = {
  automation: Automation;
  sections: Record<string, Section>;
  query: Query;
};

export type AutomationAnalyticsWindow = {
  mailpoet_automation: Automation;
};

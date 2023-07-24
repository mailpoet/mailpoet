import { Automation } from '../../../../editor/components/automation/types';

export type CurrentAndPrevious = {
  current: number;
  previous: number;
};

export type EmailStats = {
  id: number;
  order: number;
  name: string;
  previewUrl: string;
  sent: CurrentAndPrevious;
  opened: number;
  clicked: number;
  orders: number;
  revenue: number;
  unsubscribed: number;
};

type OverviewSectionData = SectionData & {
  opened: CurrentAndPrevious;
  clicked: CurrentAndPrevious;
  orders: CurrentAndPrevious;
  unsubscribed: CurrentAndPrevious;
  revenue: CurrentAndPrevious;
  sent: CurrentAndPrevious;
  emails: Record<string, EmailStats>;
};

export type SectionData = Record<string, unknown>;

type CustomQuery = {
  order: 'desc' | 'asc';
  order_by: string;
  limit: number;
  page: number;
  filter: Record<string, string[]> | undefined;
  search: string | undefined;
};

export type CurrentView = {
  filters: Record<string, string[]>;
  search?: string;
};

export type Section = {
  id: string;
  name: string;
  endpoint: string;
  customQuery?: CustomQuery;
  currentView?: CurrentView;
  withPreviousData: boolean;
  data: undefined | SectionData;
  updateCallback?: (data: SectionData | undefined) => void;
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

export type CustomerData = {
  id: number;
  email: string;
  first_name: string;
  last_name: string;
  avatar: string;
};
type LineItemData = {
  id: number;
  name: string;
  quantity: number;
};

export type OrderDetails = {
  id: number;
  status: {
    id: string;
    name: string;
  };
  total: number;
  products: LineItemData[];
};

export type OrderData = {
  date: string;
  customer: CustomerData;
  details: OrderDetails;
  email: {
    id: number;
    subject: string;
  };
};

type OrderSectionData = SectionData & {
  results: number;
  items: OrderData[];
  emails: {
    id: string;
    name: string;
  }[];
};

export type OrderSection = Section & {
  data: undefined | OrderSectionData;
  currentView: {
    filters: {
      emails: string[];
    };
  };
  updateCallback: () => void;
};

export type SubscriberData = {
  date: string;
  subscriber: {
    id: number;
    email: string;
    first_name: string;
    last_name: string;
    avatar: string;
  };
  run: {
    id: number;
    status: string;
    step: {
      id: string;
      name: string;
    };
  };
};

type SubscriberSectionData = SectionData & {
  results: number;
  items: SubscriberData[];
};

export type SubscriberSection = Section & {
  data: undefined | SubscriberSectionData;
  currentView: {
    search: string;
    filters: {
      step: string[];
      status: string[];
    };
  };
};

export type StepFlowData = {
  total: number;
  waiting: Record<string, number> | undefined;
  flow: Record<string, number> | undefined;
};
export type AutomationFlowSectionData = SectionData & {
  automation: Automation;
  step_data: StepFlowData;
  tree_is_inconsistent: boolean;
};

export type AutomationFlowSection = Section & {
  data: undefined | AutomationFlowSectionData;
};
export type State = {
  sections: Record<string, Section>;
  query: Query;
};

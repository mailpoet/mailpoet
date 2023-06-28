import { AutomationStatus } from '../../../../listing/automation';
import { Step } from '../../../../editor/components/automation/types';

export type Automation = {
  id: number;
  name: string;
  status: AutomationStatus;
  steps: Record<string, Step>;
};

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
};

export type Section = {
  id: string;
  name: string;
  endpoint: string;
  customQuery: CustomQuery | undefined;
  withPreviousData: boolean;
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
};

export type OrderSection = Section & {
  data: undefined | OrderSectionData;
};
export type State = {
  sections: Record<string, Section>;
  query: Query;
};

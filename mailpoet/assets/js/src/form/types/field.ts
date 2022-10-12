import { ComponentType, ReactNode } from 'react';
import { FieldType } from './fieldType';

export type Segment = unknown; // @TODO: add properties

export type Field = {
  name: string;
  id?: string;
  api_version?: string;
  endpoint?: string;
  tooltip?: string;
  customLabel?: string;
  className?: string;
  disabled?: false;
  label?: string;
  tip?: string | null | ReactNode | Array<ReactNode>;
  placeholder?: string;
  type?: FieldType;
  component?: ComponentType;
  fields?: Array<Field>;
  validation?: Record<string, string | boolean | number>;
  inline?: boolean;
  multiple?: boolean;
  onBeforeChange?: () => void;
  filter?: (segment: Segment) => boolean;
  getLabel?: (segment: Segment) => string;
  getCount?: (segment: Segment) => string;
  transformChangedValue?: (arg: unknown) => Segment;
  onWrapperClick?: () => void;
};

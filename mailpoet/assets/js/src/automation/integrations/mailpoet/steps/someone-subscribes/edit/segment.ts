import { FormTokenItem } from '../../../../../editor/components';

type Segment = FormTokenItem & {
  type: string;
};

declare global {
  interface Window {
    mailpoet_segments: Segment[];
  }
}

export const segments = window.mailpoet_segments;

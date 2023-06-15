export * from './dynamic/types';

export type SegmentResponse = {
  meta: {
    count: string;
  };
  data: {
    name: string;
  };
  errors: {
    message: string;
  }[];
};

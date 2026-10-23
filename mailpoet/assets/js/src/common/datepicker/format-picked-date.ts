import { format } from 'date-fns';

export const formatPickedDate = (date: Date): string =>
  format(date, 'yyyy-MM-dd');

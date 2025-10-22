import { ScheduledRow } from './components/scheduled-row';
import { RecipientsRow } from './components/recipients-row';

export function EmailStatus() {
  return (
    <>
      <ScheduledRow />
      <RecipientsRow />
    </>
  );
}

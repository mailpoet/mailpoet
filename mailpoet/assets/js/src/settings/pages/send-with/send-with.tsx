import { useParams } from 'react-router-dom';
import { SendWithChoice } from './send-with-choice';
import { OtherSendingMethods } from './other/other-sending-methods';

export function SendWith() {
  const { subPage } = useParams<{ subPage: string }>();
  return subPage === 'other' ? <OtherSendingMethods /> : <SendWithChoice />;
}

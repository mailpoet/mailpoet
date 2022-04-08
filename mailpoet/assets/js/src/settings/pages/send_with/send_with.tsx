import { useParams } from 'react-router-dom';
import SendWithChoice from './send_with_choice';
import OtherSendingMethods from './other/other_sending_methods';

export default function SendWith() {
  const { subPage } = useParams<{ subPage: string }>();
  return subPage === 'other' ? <OtherSendingMethods /> : <SendWithChoice />;
}

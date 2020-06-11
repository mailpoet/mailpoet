import React from 'react';
import { useParams } from 'react-router-dom';
import SendWithChoice from './send_with_choice';
import OtherSendingMethods from './other/other_sending_methods';

export default function SendWith() {
  const { subPage } = useParams();
  return subPage === 'other'
    ? <OtherSendingMethods />
    : <SendWithChoice />;
}

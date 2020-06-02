import React from 'react';
import { RouteComponentProps } from 'react-router-dom';
import OfferClearout from './offer_clearout';

export default ({ history }: RouteComponentProps) => (
  <OfferClearout onProceed={() => history.push('step_method_selection')} />
);

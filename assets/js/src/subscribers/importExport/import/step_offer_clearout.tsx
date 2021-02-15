import React from 'react';
import { RouteComponentProps } from 'react-router-dom';
import OfferClearout from './offer_clearout';

export default ({ history }: RouteComponentProps): JSX.Element => (
  <OfferClearout onProceed={(): void => history.push('step_method_selection')} />
);

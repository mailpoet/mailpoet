import React from 'react';
import { RouteComponentProps } from 'react-router-dom';
import CleanList from './clean_list';

export default ({ history }: RouteComponentProps): JSX.Element => (
  <CleanList onProceed={(): void => history.push('step_method_selection')} />
);

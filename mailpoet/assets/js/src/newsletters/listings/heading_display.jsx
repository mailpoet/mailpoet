import { withRouter } from 'react-router-dom';
import { mapPathToSteps } from './heading_steps.tsx';

const isHeaderHidden = (location) => location.hash.match(new RegExp('^#/new')) || location.pathname.match(new RegExp('^/new'));

const ListingHeadingDisplay = ({ children, location }) => {
  const stepNumber = mapPathToSteps(location);
  if (stepNumber === null && !isHeaderHidden(location)) {
    return children;
  }
  return null;
};

export default withRouter(ListingHeadingDisplay);

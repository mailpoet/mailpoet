import PropTypes from 'prop-types';
import { useLocation } from 'react-router-dom';
import { mapPathToSteps } from './heading-steps.tsx';

const isHeaderHidden = (location) =>
  location.hash.match(/^#\/new/) || location.pathname.match(/^\/new/);

export function ListingHeadingDisplay({ children }) {
  const location = useLocation();
  const stepNumber = mapPathToSteps(location);
  if (stepNumber === null && !isHeaderHidden(location)) {
    return children;
  }
  return null;
}

ListingHeadingDisplay.propTypes = {
  children: PropTypes.node.isRequired,
};

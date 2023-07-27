import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';
import { mapPathToSteps } from './heading_steps.tsx';

const isHeaderHidden = (location) =>
  location.hash.match(/^#\/new/) || location.pathname.match(/^\/new/);

function ListingHeadingDisplayComponent({ children, location }) {
  const stepNumber = mapPathToSteps(location);
  if (stepNumber === null && !isHeaderHidden(location)) {
    return children;
  }
  return null;
}

ListingHeadingDisplayComponent.propTypes = {
  location: PropTypes.shape({
    pathname: PropTypes.string,
    hash: PropTypes.string,
  }).isRequired,
  children: PropTypes.node.isRequired,
};

export const ListingHeadingDisplay = withRouter(ListingHeadingDisplayComponent);

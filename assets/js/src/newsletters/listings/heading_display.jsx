import { withRouter } from 'react-router-dom';
import { mapPathToSteps } from './heading_steps.jsx';

const isHeaderHidden = (location) => location.hash.match(new RegExp('^#/new')) || location.pathname.match(new RegExp('^/new'));

const showWPScreenOptions = () => {
  const screenOptions = document.getElementById('screen-meta-links');
  if (screenOptions && screenOptions.style.display === 'none') {
    screenOptions.style.display = 'block';
  }
};

const ListingHeadingDisplay = ({ children, location }) => {
  const stepNumber = mapPathToSteps(location);
  if (stepNumber === null && !isHeaderHidden(location)) {
    showWPScreenOptions();
    return children;
  }
  return null;
};

export default withRouter(ListingHeadingDisplay);

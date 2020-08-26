import { withRouter } from 'react-router-dom';
import { mapPathToSteps } from './heading_steps.jsx';

const showWPScreenOptions = () => {
  const screenOptions = document.getElementById('screen-meta-links');
  if (screenOptions && screenOptions.style.display === 'none') {
    screenOptions.style.display = 'block';
  }
};

const ListingHeadingDisplay = ({ children, location }) => {
  const stepNumber = mapPathToSteps(location);
  if (stepNumber === null) {
    showWPScreenOptions();
    return children;
  }
  return null;
};

export default withRouter(ListingHeadingDisplay);

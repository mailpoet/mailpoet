import { useLocation } from 'react-router-dom';
import { ListingHeadingSteps } from './heading-steps';

export function ListingHeadingStepsRoute(props) {
  const location = useLocation();
  return <ListingHeadingSteps {...props} location={location} />;
}

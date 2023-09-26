import { RouteComponentProps, withRouter } from 'react-router-dom';
import { ListingHeadingSteps, Props } from './heading_steps';

interface PropsWithRouter extends RouteComponentProps, Props {}

export const ListingHeadingStepsRoute = withRouter((props: PropsWithRouter) => (
  <ListingHeadingSteps {...props} />
));

import { withRouter, RouteComponentProps } from 'react-router-dom';
import ListingHeadingSteps, { Props } from './heading_steps';

interface PropsWithRouter extends RouteComponentProps, Props {}

const ListingHeadingStepsRoute = withRouter((props: PropsWithRouter) => (
  <ListingHeadingSteps {...props} />
));

export default ListingHeadingStepsRoute;

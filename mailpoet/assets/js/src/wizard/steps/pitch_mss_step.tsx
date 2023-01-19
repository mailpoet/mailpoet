import {
  useRouteMatch,
  Switch,
  Route,
  Redirect,
  useParams,
} from 'react-router-dom';
import { MSSStepFirstPart } from './pitch_mss_step/first_part';
import { MSSStepSecondPart } from './pitch_mss_step/second_part';
import { MSSStepThirdPart } from './pitch_mss_step/third_part';

function WelcomeWizardPitchMSSStep(): JSX.Element {
  const { path } = useRouteMatch();
  const { step } = useParams<{ step: string }>();

  return (
    <Switch>
      <Route exact path={`${path}`}>
        <Redirect to={`/steps/${step}/part/1`} />
      </Route>
      <Route path={`${path}/part/1`}>
        <MSSStepFirstPart />
      </Route>
      <Route path={`${path}/part/2`}>
        <MSSStepSecondPart />
      </Route>
      <Route path={`${path}/part/3`}>
        <MSSStepThirdPart />
      </Route>
    </Switch>
  );
}

export { WelcomeWizardPitchMSSStep };

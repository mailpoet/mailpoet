import {
  useRouteMatch,
  Switch,
  Route,
  useParams,
  useHistory,
  useLocation,
} from 'react-router-dom';
import { useEffect } from 'react';
import { MSSStepFirstPart } from './pitch-mss-step/first-part';
import { MSSStepSecondPart } from './pitch-mss-step/second-part';
import { MSSStepThirdPart } from './pitch-mss-step/third-part';
import { navigateToPath } from '../steps-numbers';

function WelcomeWizardPitchMSSStep(): JSX.Element {
  const { path } = useRouteMatch();
  const { step } = useParams<{ step: string }>();
  const history = useHistory();
  const location = useLocation();

  useEffect(() => {
    if (!location.pathname.includes('part')) {
      navigateToPath(history, `/steps/${step}/part/1`);
    }
  }, [step, path, history, location]);

  return (
    <Switch>
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

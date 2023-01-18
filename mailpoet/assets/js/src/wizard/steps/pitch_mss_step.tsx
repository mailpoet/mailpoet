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

type WelcomeWizardPitchMSSStepPropType = {
  finishWizard: (redirect_url?: string) => void;
};

function WelcomeWizardPitchMSSStep({
  finishWizard,
}: WelcomeWizardPitchMSSStepPropType): JSX.Element {
  const { path } = useRouteMatch();
  const { step } = useParams<{ step: string }>();

  return (
    <Switch>
      <Route exact path={`${path}`}>
        <Redirect to={`/steps/${step}/part/1`} />
      </Route>
      <Route path={`${path}/part/1`}>
        <MSSStepFirstPart finishWizard={finishWizard} />
      </Route>
      <Route path={`${path}/part/2`}>
        <MSSStepSecondPart finishWizard={finishWizard} />
      </Route>
      <Route path={`${path}/part/3`}>
        <MSSStepThirdPart finishWizard={finishWizard} />
      </Route>
    </Switch>
  );
}

export { WelcomeWizardPitchMSSStep };

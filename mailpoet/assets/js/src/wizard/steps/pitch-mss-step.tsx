import {
  Routes,
  Route,
  useParams,
  useNavigate,
  useLocation,
} from 'react-router-dom';
import { useEffect } from 'react';
import { MSSStepFirstPart } from './pitch-mss-step/first-part';
import { MSSStepSecondPart } from './pitch-mss-step/second-part';
import { MSSStepThirdPart } from './pitch-mss-step/third-part';
import { navigateToPath } from '../navigate-to-path';

function WelcomeWizardPitchMSSStep(): JSX.Element {
  const { pathname } = useLocation();
  const { step } = useParams<{ step: string }>();
  const navigate = useNavigate();
  const location = useLocation();

  useEffect(() => {
    if (!location.pathname.includes('part')) {
      navigateToPath(navigate, `/steps/${step}/part/1`, true);
    }
  }, [step, pathname, navigate, location]);

  return (
    <Routes>
      <Route path="part/1" element={<MSSStepFirstPart />} />
      <Route path="part/2" element={<MSSStepSecondPart />} />
      <Route path="part/3" element={<MSSStepThirdPart />} />
    </Routes>
  );
}

export { WelcomeWizardPitchMSSStep };

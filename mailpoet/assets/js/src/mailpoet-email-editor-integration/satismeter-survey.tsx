import { Button } from '@wordpress/components';
import { useLayoutEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { initializeSatismeterSurvey } from 'nps-poll';

const emailEditorSatismeterWriteId = '9qCj2SJBE1s5OhnX5NYfRXu82pEDUB9x';

export function withSatismeterSurvey(Component) {
  return function WrappedBySurvey(props) {
    const [surveyAvailable, setSurveyAvailable] = useState(false);

    const triggerSurvey = () => {
      // The survey is configured to open when we track the 'Request feedback' event
      window.satismeter('track', { event: 'Request feedback' });
    };

    useLayoutEffect(() => {
      // Initialize Satismeter Survey for the email editor
      void initializeSatismeterSurvey(emailEditorSatismeterWriteId)
        .then(() => {
          if (!window.satismeter) {
            return;
          }
          setSurveyAvailable(true);
        })
        // Survey may fail to initialize when 3rd party libs are not allowed. It is OK we don't need to react.
        .catch(() => {});
    }, []);

    return (
      <>
        <Component {...props} />
        {surveyAvailable && (
          <Button
            isPrimary
            style={{ position: 'absolute', right: '10px', bottom: '10px' }}
            onClick={triggerSurvey}
          >
            {__('Share feedback', 'mailpoet')}
          </Button>
        )}
      </>
    );
  };
}

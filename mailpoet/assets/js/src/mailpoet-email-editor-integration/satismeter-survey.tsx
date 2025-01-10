import { Button } from '@wordpress/components';
import { useLayoutEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { initializeSatismeterSurvey } from 'nps-poll';

const emailEditorSatismeterWriteId = '9qCj2SJBE1s5OhnX5NYfRXu82pEDUB9x';

export function withSatismeterSurvey(Component) {
  return function WrappedBySurvey(props) {
    const triggerSurvey = () => {
      // The survey is configured to open when we track the 'Request feedback' event
      window.satismeter('track', { event: 'Request feedback' });
    };

    useLayoutEffect(() => {
      // Initialize Satismeter Survey for the email editor
      initializeSatismeterSurvey(emailEditorSatismeterWriteId);
    }, []);

    return (
      <>
        <Component {...props} />
        <Button
          isPrimary
          style={{ position: 'absolute', right: '10px', bottom: '10px' }}
          onClick={triggerSurvey}
        >
          {__('Share feedback', 'mailpoet')}
        </Button>
      </>
    );
  };
}

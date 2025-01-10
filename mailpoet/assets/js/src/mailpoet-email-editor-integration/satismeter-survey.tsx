import { MailPoet } from 'mailpoet';
import { Button } from '@wordpress/components';
import { useLayoutEffect, useState } from '@wordpress/element';
import { commentContent } from '@wordpress/icons';
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

          // We want to show the survey immediately when there has been enough usage
          if (window.mailpoet_display_nps_email_editor) {
            window.mailpoet_display_nps_email_editor = false;
            void MailPoet.Ajax.post({
              api_version: MailPoet.apiVersion,
              endpoint: 'user_flags',
              action: 'set',
              data: {
                email_editor_survey_seen: MailPoet.Date.toGmtDatetimeString(
                  new Date(),
                ),
              },
            }).then(triggerSurvey);
          }
        })
        // Survey may fail to initialize when 3rd party libs are not allowed. It is OK we don't need to react.
        .catch(() => {});
    }, []);

    return (
      <>
        <Component {...props} />
        {surveyAvailable && (
          <Button
            icon={commentContent}
            variant="tertiary"
            className="mailpoet-editor-feedback-button"
            onClick={triggerSurvey}
          >
            {__('Share feedback', 'mailpoet')}
          </Button>
        )}
      </>
    );
  };
}

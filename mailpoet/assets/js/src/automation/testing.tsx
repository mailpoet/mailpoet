import { useMutation } from './api';
import { id } from './id';

const createSendWelcomeEmailStep = () => ({
  id: id(),
  type: 'action',
  key: 'mailpoet:send-welcome-email',
  args: {
    welcomeEmailId: 1,
  },
});

const createWaitStep = (nextStepId: string) => ({
  id: id(),
  type: 'action',
  key: 'core:wait',
  next_step_id: nextStepId,
  args: {
    seconds: 60,
  },
});

const createTrigger = (nextStepId: string) => ({
  id: id(),
  type: 'trigger',
  key: 'mailpoet:segment:subscribed',
  next_step_id: nextStepId,
});

const createWorkflow = () => {
  const sendWelcomeEmail = createSendWelcomeEmailStep();
  const wait = createWaitStep(sendWelcomeEmail.id);
  const trigger = createTrigger(wait.id);
  return {
    name: `Test ${new Date().toISOString()}`,
    steps: {
      [trigger.id]: trigger,
      [wait.id]: wait,
      [sendWelcomeEmail.id]: sendWelcomeEmail,
    },
  };
};

export function CreateTestingWorkflowButton(): JSX.Element {
  const [createSchema, { loading, error }] = useMutation('workflows', {
    method: 'POST',
  });

  return (
    <div>
      <button
        type="button"
        onClick={() =>
          createSchema({
            body: JSON.stringify(createWorkflow()),
          })
        }
        disabled={loading}
      >
        Create testing workflow
      </button>
      {error && (
        <div>{error?.data?.message ?? 'An unknown error occurred'}</div>
      )}
    </div>
  );
}

export function CreateWorkflowFromTemplateButton(): JSX.Element {
  const [createWorkflowFromTemplate, { loading, error }] = useMutation(
    'workflows/create-from-template',
    {
      method: 'POST',
    },
  );

  return (
    <div>
      <button
        type="button"
        onClick={() =>
          createWorkflowFromTemplate({
            body: JSON.stringify({
              name: `Test from template ${new Date().toISOString()}`,
              template: 'delayed-email-after-signup',
            }),
          })
        }
        disabled={loading}
      >
        Create testing workflow from template
      </button>
      {error && (
        <div>{error?.data?.message ?? 'An unknown error occurred'}</div>
      )}
    </div>
  );
}

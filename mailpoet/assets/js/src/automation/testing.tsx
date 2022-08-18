import { ReactNode } from 'react';
import { useMutation } from './api';
import { id } from './id';

const createSendEmailStep = () => ({
  id: id(),
  type: 'action',
  key: 'mailpoet:send-email',
  args: {
    email_id: 1,
  },
});

const createDelayStep = (nextStepId: string) => ({
  id: id(),
  type: 'action',
  key: 'core:delay',
  next_step_id: nextStepId,
  args: {
    delay: 1,
    delay_type: 'HOURS',
  },
});

const createTrigger = (nextStepId: string) => ({
  id: id(),
  type: 'trigger',
  key: 'mailpoet:segment:subscribed',
  next_step_id: nextStepId,
});

const createWorkflow = () => {
  const sendEmail = createSendEmailStep();
  const delay = createDelayStep(sendEmail.id);
  const trigger = createTrigger(delay.id);
  return {
    name: `Test ${new Date().toISOString()}`,
    steps: {
      [trigger.id]: trigger,
      [delay.id]: delay,
      [sendEmail.id]: sendEmail,
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
        className="button"
        type="button"
        onClick={async () => {
          await createSchema({
            body: JSON.stringify(createWorkflow()),
          });
          window.location.reload();
        }}
        disabled={loading}
      >
        Create testing workflow (premium required)
      </button>
      {error && (
        <div>{error?.data?.message ?? 'An unknown error occurred'}</div>
      )}
    </div>
  );
}

type TemplateButtonProps = {
  slug: string;
  children?: ReactNode;
};

export function CreateWorkflowFromTemplateButton({
  slug,
  children,
}: TemplateButtonProps): JSX.Element {
  const [createWorkflowFromTemplate, { loading, error }] = useMutation(
    'workflows/create-from-template',
    {
      method: 'POST',
      body: JSON.stringify({
        slug,
      }),
    },
  );

  return (
    <div>
      <button
        className="button button-primary"
        type="button"
        onClick={async () => {
          await createWorkflowFromTemplate();
          window.location.reload();
        }}
        disabled={loading}
      >
        {children}
      </button>
      {error && (
        <div>{error?.data?.message ?? 'An unknown error occurred'}</div>
      )}
    </div>
  );
}

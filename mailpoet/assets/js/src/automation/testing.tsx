import { useMutation } from './api';
import { id } from './id';

const createWaitStep = () => ({
  id: id(),
  type: 'action',
  key: 'core:wait',
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
  const wait = createWaitStep();
  const trigger = createTrigger(wait.id);
  return {
    name: `Test ${new Date().toISOString()}`,
    steps: {
      [trigger.id]: trigger,
      [wait.id]: wait,
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

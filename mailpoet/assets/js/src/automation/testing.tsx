import { ReactNode } from 'react';
import { useMutation } from './api';
import { Step, Automation } from './editor/components/automation/types';

export const createRootStep = (): Step => ({
  id: 'root',
  type: 'root',
  key: 'core:root',
  args: {},
  next_steps: [],
});

const createAutomation = (): Partial<Automation> => ({
  name: 'Empty automation',
  steps: {
    root: createRootStep(),
  },
});

export function CreateEmptyAutomationButton(): JSX.Element {
  const [createSchema, { loading, error }] = useMutation('automations', {
    method: 'POST',
  });

  return (
    <div>
      <button
        className="button"
        type="button"
        onClick={async () => {
          await createSchema({
            body: JSON.stringify(createAutomation()),
          });
          window.location.reload();
        }}
        disabled={loading}
      >
        Create empty automation (premium required)
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

export function CreateAutomationFromTemplateButton({
  slug,
  children,
}: TemplateButtonProps): JSX.Element {
  const [createAutomationFromTemplate, { loading, error }] = useMutation(
    'automations/create-from-template',
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
          await createAutomationFromTemplate();
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

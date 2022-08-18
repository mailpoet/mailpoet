import ReactDOM from 'react-dom';
import { Workflow } from './listing/workflow';
import { AutomationListing } from './listing';
import { Onboarding } from './onboarding';
import {
  CreateTestingWorkflowButton,
  CreateWorkflowFromTemplateButton,
} from './testing';
import { useMutation, useQuery } from './api';

function Workflows(): JSX.Element {
  const { data, loading, error } = useQuery<{ data: Workflow[] }>('workflows');

  if (error) {
    return <div>Error: {error}</div>;
  }

  if (loading) {
    return <div>Loading workflows...</div>;
  }

  const workflows = data?.data ?? [];
  return workflows.length === 0 ? (
    <Onboarding />
  ) : (
    <AutomationListing workflows={workflows} loading={loading} />
  );
}

function RecreateSchemaButton(): JSX.Element {
  const [createSchema, { loading, error }] = useMutation('system/database', {
    method: 'POST',
  });

  return (
    <div>
      <button
        className="button button-link-delete"
        type="button"
        onClick={() => createSchema()}
        disabled={loading}
      >
        Recreate DB schema (data will be lost)
      </button>
      {error && (
        <div>{error?.data?.message ?? 'An unknown error occurred'}</div>
      )}
    </div>
  );
}

function DeleteSchemaButton(): JSX.Element {
  const [deleteSchema, { loading, error }] = useMutation('system/database', {
    method: 'DELETE',
  });

  return (
    <div>
      <button
        className="button button-link-delete"
        type="button"
        onClick={async () => {
          await deleteSchema();
          window.location.href =
            '/wp-admin/admin.php?page=mailpoet-experimental';
        }}
        disabled={loading}
      >
        Delete DB schema & deactivate feature
      </button>
      {error && (
        <div>{error?.data?.message ?? 'An unknown error occurred'}</div>
      )}
    </div>
  );
}

function App(): JSX.Element {
  return (
    <div>
      <Workflows />
      <div style={{ marginTop: 30, display: 'grid', gridGap: 8 }}>
        <CreateTestingWorkflowButton />
        <CreateWorkflowFromTemplateButton slug="simple-welcome-email">
          Create testing workflow from template (welcome email)
        </CreateWorkflowFromTemplateButton>
        <CreateWorkflowFromTemplateButton slug="welcome-email-sequence">
          Create testing workflow from template (welcome sequence, only premium)
        </CreateWorkflowFromTemplateButton>
        <CreateWorkflowFromTemplateButton slug="advanced-welcome-email-sequence">
          Create testing workflow from template (advanced welcome sequence, only
          premium)
        </CreateWorkflowFromTemplateButton>
        <RecreateSchemaButton />
        <DeleteSchemaButton />
      </div>
    </div>
  );
}

window.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('mailpoet_automation');
  if (root) {
    ReactDOM.render(<App />, root);
  }
});

import ReactDOM from 'react-dom';
import { Workflow } from './list/workflow';
import { AutomationListing } from './list/automation-listing';
import { Onboarding } from './onboarding/onboarding';
import { Loading } from '../common';
import {
  CreateTestingWorkflowButton,
  CreateWorkflowFromTemplateButton,
} from './testing';
import { useMutation, useQuery } from './api';

function Workflows(): JSX.Element {
  const { data, loading, error } = useQuery('workflows');

  if (error) {
    return <div>Error: {error}</div>;
  }

  const workflows: Workflow[] = data?.data ?? [];
  if (loading) {
    return <Loading />;
  }
  return workflows.length === 0 ? (
    Onboarding()
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
      <button type="button" onClick={() => createSchema()} disabled={loading}>
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
      <CreateTestingWorkflowButton />
      <CreateWorkflowFromTemplateButton />
      <RecreateSchemaButton />
      <DeleteSchemaButton />
    </div>
  );
}

window.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('mailpoet_automation');
  if (root) {
    ReactDOM.render(<App />, root);
  }
});

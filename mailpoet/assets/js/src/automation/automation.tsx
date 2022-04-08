import ReactDOM from 'react-dom';
import { CreateTestingWorkflowButton } from './testing';
import { useMutation, useQuery } from './api';

function ApiCheck(): JSX.Element {
  const { data, loading, error } = useQuery('workflows');

  if (!data || loading) {
    return <div>Calling API...</div>;
  }

  return <div>{error ? 'API error!' : 'API OK ✓'}</div>;
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
      <ApiCheck />
      <CreateTestingWorkflowButton />
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

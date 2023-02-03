import { RolePanel } from './role_panel';
import { RunOnlyOncePanel } from '../../../../shared/run-only-once-panel';

export function Edit(): JSX.Element {
  return (
    <>
      <RunOnlyOncePanel />
      <RolePanel />
    </>
  );
}

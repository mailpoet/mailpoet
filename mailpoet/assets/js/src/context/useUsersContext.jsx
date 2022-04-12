import { useMemo } from 'react';

export function useUsersContext(data) {
  return useMemo(
    () => ({
      isNewUser: data.mailpoet_is_new_user,
    }),
    [data],
  );
}

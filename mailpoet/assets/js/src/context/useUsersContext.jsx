import React from 'react';

export default function useUsersContext(data) {
  return React.useMemo(() => ({
    isNewUser: data.mailpoet_is_new_user,
  }), [data]);
}

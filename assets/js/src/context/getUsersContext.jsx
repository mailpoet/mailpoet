export default function getUsersContext(data) {
  return {
    isNewUser: data.mailpoet_is_new_user,
  };
}

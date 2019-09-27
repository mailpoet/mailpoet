export default function getConstantsContext(data) {
  return {
    isNewUser: data.mailpoet_is_new_user,
    segments: data.mailpoetSegments,
  };
}

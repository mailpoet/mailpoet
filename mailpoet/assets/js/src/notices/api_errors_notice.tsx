import Notice from 'notices/notice';

type Props = {
  errors: Array<{ message: string }>;
};

function APIErrorsNotice({ errors }: Props) {
  if (errors.length < 1) return null;
  return (
    <Notice type="error" closable={false}>
      {errors.map((err) => (
        <p key={err.message}>{err.message}</p>
      ))}
    </Notice>
  );
}

export default APIErrorsNotice;

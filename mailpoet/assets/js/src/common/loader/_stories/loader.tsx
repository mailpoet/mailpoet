import Loader from '../loader';

export default {
  title: 'Loader',
  component: Loader,
};

export function Loaders() {
  return (
    <>
      <p>
        Default loader: <Loader />
      </p>
      <p>
        Light loader: <Loader variant="light" />
      </p>
      <p>
        Dark loader: <Loader variant="dark" />
      </p>
      <p>
        bigger loader: <Loader size={64} />
      </p>
    </>
  );
}

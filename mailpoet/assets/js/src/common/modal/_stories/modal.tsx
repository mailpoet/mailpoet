import { useState } from 'react';
import Button from '../../button/button';
import Modal from '../modal';
import Heading from '../../typography/heading/heading';

const shortContent = (
  <>
    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
    <p>
      Morbi libero sapien, tristique sollicitudin lobortis id, viverra id
      libero.
    </p>
    <p>Mauris dolor felis, sagittis at, luctus sed, aliquam non, tellus.</p>
  </>
);

const longContent = (
  <>
    <p>
      {'Lorem ipsum dolor sit amet, consectetur adipiscing elit. '.repeat(20)}
    </p>
    <p>
      {'Morbi libero sapien, tristique sollicitudin lobortis id, viverra id libero. '.repeat(
        20,
      )}
    </p>
    <p>
      {'Mauris dolor felis, sagittis at, luctus sed, aliquam non, tellus. '.repeat(
        20,
      )}
    </p>
    <p>{'Vivamus ac leo pretium faucibus.'.repeat(20)}</p>
    <p>
      {'Etiam dui sem, fermentum vitae, sagittis id, malesuada in, quam. '.repeat(
        20,
      )}
    </p>
    <p>
      {'Duis sapien nunc, commodo et, interdum suscipit, sollicitudin et, dolor. '.repeat(
        20,
      )}
    </p>
    <p>
      {'Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. '.repeat(
        20,
      )}
    </p>
    <p>
      {'Cras pede libero, dapibus nec, pretium sit amet, tempor quis. '.repeat(
        20,
      )}
    </p>
  </>
);

export default {
  title: 'Modal',
  component: Modal,
};

function ModalWrapper({
  buttonCaption,
  title = 'Modal title',
  isDismissible = true,
  content = shortContent,
  fullScreen = false,
}) {
  const [showModal, setShowModal] = useState(false);
  return (
    <p>
      <Button
        onClick={() => setShowModal(true)}
        dimension="small"
        variant="secondary"
      >
        {buttonCaption}
      </Button>
      {showModal && (
        <Modal
          title={title}
          onRequestClose={() => setShowModal(false)}
          isDismissible={isDismissible}
          fullScreen={fullScreen}
        >
          {content}
        </Modal>
      )}
    </p>
  );
}

export function Modals() {
  return (
    <>
      <Heading level={3}>Modal with short text</Heading>
      <ModalWrapper buttonCaption="Show modal with title, with close button" />
      <ModalWrapper
        buttonCaption="Show modal with title, without close button"
        isDismissible={false}
      />
      <ModalWrapper
        buttonCaption="Show modal without title, with close button"
        title={null}
      />
      <ModalWrapper
        buttonCaption="Show modal without title, without close button"
        title={null}
        isDismissible={false}
      />

      <div className="mailpoet-gap" />

      <Heading level={3}>Modal with long text</Heading>
      <ModalWrapper
        buttonCaption="Show modal with title, with close button"
        content={longContent}
      />
      <ModalWrapper
        buttonCaption="Show modal with title, without close button"
        isDismissible={false}
        content={longContent}
      />
      <ModalWrapper
        buttonCaption="Show modal without title, with close button"
        title={null}
        content={longContent}
      />
      <ModalWrapper
        buttonCaption="Show modal without title, without close button"
        title={null}
        isDismissible={false}
        content={longContent}
      />

      <div className="mailpoet-gap" />

      <Heading level={3}>Full-screen modal</Heading>
      <ModalWrapper
        buttonCaption="Show modal with title, with close button"
        content={longContent}
        fullScreen
      />
      <ModalWrapper
        buttonCaption="Show modal with title, without close button"
        isDismissible={false}
        content={longContent}
        fullScreen
      />
      <ModalWrapper
        buttonCaption="Show modal without title, with close button"
        title={null}
        content={longContent}
        fullScreen
      />
      <ModalWrapper
        buttonCaption="Show modal without title, without close button"
        title={null}
        isDismissible={false}
        content={longContent}
        fullScreen
      />
    </>
  );
}

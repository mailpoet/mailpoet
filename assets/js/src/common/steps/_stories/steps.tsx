import React from 'react';
import Steps from '../steps';
import StepsContent from '../steps_content';

export default {
  title: 'Steps',
};

export const StepsWithoutTitles = () => (
  <>
    <Steps count={5} current={1} />
    <StepsContent>
      Lorem ipsum dolor sit amet consectetur adipisicing elit. Soluta natus
      consequuntur saepe harum nesciunt eum, a nulla facilis architecto incidunt
      odio voluptas praesentium, ipsa laboriosam animi! Officiis atque odio nulla.
    </StepsContent>

    <div className="mailpoet-gap" />

    <Steps count={5} current={3} />
    <StepsContent>
      Lorem ipsum dolor sit amet consectetur adipisicing elit. Soluta natus
      consequuntur saepe harum nesciunt eum, a nulla facilis architecto incidunt
      odio voluptas praesentium, ipsa laboriosam animi! Officiis atque odio nulla.
    </StepsContent>

    <div className="mailpoet-gap" />

    <Steps count={5} current={5} />
    <StepsContent>
      Lorem ipsum dolor sit amet consectetur adipisicing elit. Soluta natus
      consequuntur saepe harum nesciunt eum, a nulla facilis architecto incidunt
      odio voluptas praesentium, ipsa laboriosam animi! Officiis atque odio nulla.
    </StepsContent>
  </>
);

export const StepsWithTitles = () => (
  <>
    <Steps count={4} current={1} titles={['First', 'Second', 'Third', 'Fourth']} />
    <StepsContent>
      Lorem ipsum dolor sit amet consectetur adipisicing elit. Soluta natus
      consequuntur saepe harum nesciunt eum, a nulla facilis architecto incidunt
      odio voluptas praesentium, ipsa laboriosam animi! Officiis atque odio nulla.
    </StepsContent>

    <div className="mailpoet-gap" />

    <Steps count={4} current={3} titles={['First', 'Second', 'Third', 'Fourth']} />
    <StepsContent>
      Lorem ipsum dolor sit amet consectetur adipisicing elit. Soluta natus
      consequuntur saepe harum nesciunt eum, a nulla facilis architecto incidunt
      odio voluptas praesentium, ipsa laboriosam animi! Officiis atque odio nulla.
    </StepsContent>

    <div className="mailpoet-gap" />

    <Steps count={4} current={4} titles={['First', 'Second', 'Third', 'Fourth']} />
    <StepsContent>
      Lorem ipsum dolor sit amet consectetur adipisicing elit. Soluta natus
      consequuntur saepe harum nesciunt eum, a nulla facilis architecto incidunt
      odio voluptas praesentium, ipsa laboriosam animi! Officiis atque odio nulla.
    </StepsContent>
  </>
);

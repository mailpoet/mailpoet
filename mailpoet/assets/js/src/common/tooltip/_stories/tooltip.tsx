import Heading from 'common/typography/heading/heading';
import Tooltip from '../tooltip';

export default {
  title: 'Tooltips',
};

export function Tooltips() {
  return (
    <>
      <Heading level={1}>Placements</Heading>
      <div style={{ display: 'flex', justifyContent: 'space-around' }}>
        <p data-tip data-for="bottom">
          Bottom Tooltip
        </p>
        <Tooltip id="bottom" place="bottom">
          <span>The tooltip content</span>
        </Tooltip>

        <p data-tip data-for="top">
          Top Tooltip
        </p>
        <Tooltip id="top" place="top">
          <span>The tooltip content</span>
        </Tooltip>

        <p data-tip data-for="right">
          Right Tooltip
        </p>
        <Tooltip id="right" place="right">
          <span>The tooltip content</span>
        </Tooltip>

        <p data-tip data-for="left">
          Left Tooltip
        </p>
        <Tooltip id="left" place="left">
          <span>The tooltip content</span>
        </Tooltip>
      </div>
    </>
  );
}

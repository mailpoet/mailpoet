import {
  __unstableMotion as motion,
  __experimentalHStack as HStack,
  __experimentalVStack as VStack,
} from '@wordpress/components';

const firstFrame = {
  start: {
    scale: 1,
    opacity: 1,
  },
  hover: {
    scale: 0,
    opacity: 0,
  },
};

const midFrame = {
  hover: {
    opacity: 1,
  },
  start: {
    opacity: 0.5,
  },
};

const secondFrame = {
  hover: {
    scale: 1,
    opacity: 1,
  },
  start: {
    scale: 0,
    opacity: 0,
  },
};

const normalizedHeight = 152;
const normalizedColorSwatchSize = 32;

/**
 * Component to render the styles preview based on the component from the site editor:
 * https://github.com/WordPress/gutenberg/blob/trunk/packages/edit-site/src/components/global-styles/preview.js
 */
export function Preview(): JSX.Element {
  const style = {
    backgroundColor: '#f3f3f3',
    headingFontFamily: 'Arial',
    headingColor: '#000000',
    headingFontWeight: 'normal',
    paletteColors: [
      {
        name: 'Sample Background',
        slug: 'Sample-background',
        color: '#f9f8f3',
      },
    ],
    highlightedColors: [
      {
        name: 'Sample primary',
        slug: 'sample-primary',
        color: '#e5e2d3',
      },
      {
        name: 'Sample Secondary',
        slug: 'sample-secondary',
        color: '#111111',
      },
    ],
  };

  const isFocused = false;
  const isHovered = false;
  const disableMotion = false;
  const label = 'Some Label';
  const ratio = 1;

  const gradientValue = undefined;
  const withHoverView = true;

  return (
    <motion.div
      style={{
        height: normalizedHeight * ratio,
        width: '100%',
        background: gradientValue ?? style.backgroundColor,
        cursor: withHoverView ? 'pointer' : undefined,
      }}
      initial="start"
      animate={
        (isHovered || isFocused) && !disableMotion && label ? 'hover' : 'start'
      }
    >
      <motion.div
        variants={firstFrame}
        style={{
          height: '100%',
          overflow: 'hidden',
        }}
      >
        <HStack
          spacing={10 * ratio}
          justify="center"
          style={{
            height: '100%',
            overflow: 'hidden',
          }}
        >
          <motion.div
            style={{
              fontFamily: style.headingFontFamily,
              fontSize: 65 * ratio,
              color: style.headingColor,
              fontWeight: style.headingFontWeight,
            }}
            animate={{ scale: 1, opacity: 1 }}
            initial={{ scale: 0.1, opacity: 0 }}
            transition={{ delay: 0.3, type: 'tween' }}
          >
            Aa
          </motion.div>
          <VStack spacing={4 * ratio}>
            {style.highlightedColors.map(({ slug, color }, index) => (
              <motion.div
                key={slug}
                style={{
                  height: normalizedColorSwatchSize * ratio,
                  width: normalizedColorSwatchSize * ratio,
                  background: color,
                  borderRadius: (normalizedColorSwatchSize * ratio) / 2,
                }}
                animate={{
                  scale: 1,
                  opacity: 1,
                }}
                initial={{
                  scale: 0.1,
                  opacity: 0,
                }}
                transition={{
                  delay: index === 1 ? 0.2 : 0.1,
                }}
              />
            ))}
          </VStack>
        </HStack>
      </motion.div>
      <motion.div
        variants={withHoverView && midFrame}
        style={{
          height: '100%',
          width: '100%',
          position: 'absolute',
          top: 0,
          overflow: 'hidden',
          filter: 'blur(60px)',
          opacity: 0.1,
        }}
      >
        <HStack
          spacing={0}
          justify="flex-start"
          style={{
            height: '100%',
            overflow: 'hidden',
          }}
        >
          {style.paletteColors.slice(0, 4).map(({ color }) => (
            <div
              key={color}
              style={{
                height: '100%',
                background: color,
                flexGrow: 1,
              }}
            />
          ))}
        </HStack>
      </motion.div>
      <motion.div
        variants={secondFrame}
        style={{
          height: '100%',
          width: '100%',
          overflow: 'hidden',
          position: 'absolute',
          top: 0,
        }}
      >
        <VStack
          spacing={3 * ratio}
          justify="center"
          style={{
            height: '100%',
            overflow: 'hidden',
            padding: 10 * ratio,
            boxSizing: 'border-box',
          }}
        >
          {label && (
            <div
              style={{
                fontSize: 40 * ratio,
                fontFamily: style.headingFontFamily,
                color: style.headingColor,
                fontWeight: style.headingFontWeight,
                lineHeight: '1em',
                textAlign: 'center',
              }}
            >
              {label}
            </div>
          )}
        </VStack>
      </motion.div>
    </motion.div>
  );
}

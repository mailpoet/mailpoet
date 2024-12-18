const EMAIL_STRING = 'email-editor-events';

const dispatcher = new EventTarget();

const recordEvent = ( name: string, data = {} ) => {
	const recordedData = typeof data !== 'object' ? { data } : data;

	const eventData = {
		name: `${ EMAIL_STRING }_${ name }`,
		...recordedData,
	};

	dispatcher.dispatchEvent(
		new CustomEvent( EMAIL_STRING, { detail: eventData } )
	);
};

const recordEventOnce = ( function () {
	const cachedEventName = {};
	return ( name: string, data = {} ) => {
		const cacheKey = `${ name }_${ JSON.stringify( data ).length }`; // ensure each entry is unique by name and data
		if ( cachedEventName[ cacheKey ] ) {
			return; // do not execute again
		}
		recordEvent( name, data );
		cachedEventName[ cacheKey ] = true;
	};
} )();

export { recordEvent, recordEventOnce, EMAIL_STRING, dispatcher };

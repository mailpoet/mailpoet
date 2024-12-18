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
		if ( cachedEventName[ name ] ) {
			return; // do not execute again
		}
		recordEvent( name, data );
		cachedEventName[ name ] = true;
	};
} )();

export { recordEvent, recordEventOnce, EMAIL_STRING, dispatcher };

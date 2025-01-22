/**
 * Internal dependencies
 */
import { initialize } from './editor';
import { initialize as DVInit } from './data-views';

window.addEventListener( 'DOMContentLoaded', () => {
	initialize( 'mailpoet-email-editor' );
	DVInit( 'mailpoet-emails-data-views' );
} );

/**
 * Look up a registered component in the `window.MilliBase.customComponents`
 * registry. Returns null when the name is unset or not registered.
 */

const resolveCustomComponent = ( name ) =>
	( name && window.MilliBase?.customComponents?.[ name ] ) || null;

export default resolveCustomComponent;

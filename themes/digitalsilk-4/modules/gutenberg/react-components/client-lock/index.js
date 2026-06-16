// src/utilities/ClientLockControl.js

/**
 * Check if the client is locked (i.e., not a super admin).
 *
 * @return {boolean} - True if locked, false if unlocked (super admin).
 */
export const isClientLocked = () => {
	return window?.ds?.isSuperAdmin !== true;
};

/**
 * ClientLockControl component
 *
 * Renders children only if `window.ds.isSuperAdmin` is true.
 * This version avoids state/hooks for max performance across many instances.
 *
 * @param {Object}          props          - Component props.
 * @param {React.ReactNode} props.children - Children to render.
 * @return {React.ReactNode|null} - Rendered component or null.
 */
export const ClientLockControl = ( { children } ) => {
	return ! isClientLocked() ? <>{ children }</> : null;
};

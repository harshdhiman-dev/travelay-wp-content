<?php
/**
 * Refresh token lock.
 *
 * Hosts the logic required to prevent repeated refresh token requests.
 *
 * @since 2.2.0
 */
class GF_HubSpot_Refresh_Lock_Handler {

	/**
	 * Failed request count option key.
	 *
	 * @since 2.2.0
	 *
	 * @var string
	 */
	const FAILED_REQUESTS_COUNT_OPTION_KEY = 'gf_hubspot_failed_refresh_token_requests_count';

	/**
	 * How many seconds until the rate limiting cache lock is cleared.
	 *
	 * @since 2.2.0
	 *
	 * @var integer
	 */
	const RATE_LIMIT_CACHE_EXPIRATION_SECONDS = MINUTE_IN_SECONDS;

	/**
	 * How many failed refresh requests until the refresh is locked becayse of rate limiting.
	 *
	 * @since 2.2.0
	 *
	 * @var integer
	 */
	const RATE_LIMIT_FAILED_REQUEST_THRESHOLD = 3;

	/**
	 * How many seconds until the refresh in progress cache lock is cleared.
	 *
	 * @since 2.2.0
	 *
	 * @var integer
	 */
	const REFRESH_IN_PROGRESS_EXPIRATION_SECONDS = MINUTE_IN_SECONDS;

	/**
	 * The refresh in progress cache key.
	 *
	 * @since 2.2.0
	 *
	 * @var string
	 */
	protected $refresh_in_progress_lock_key = '';

	/**
	 * The rate limiting cache key.
	 *
	 * @since 2.2.0
	 *
	 * @var string
	 */
	protected $rate_limit_lock_key = '';

	/**
	 * The reason why the refreshing could be locked.
	 *
	 * @since 2.2.0
	 *
	 * @var string
	 */
	public $refresh_lock_reason = '';

	/**
	 * An instance of the add-on.
	 *
	 * @since 2.2.0
	 *
	 * @var GF_HubSpot
	 */
	protected $addon;

	/**
	 * Handler constructor.
	 *
	 * @since 2.2.0
	 *
	 * @param GF_HubSpot $addon
	 */
	public function __construct( $addon ) {
		$this->addon                        = $addon;
		$this->refresh_in_progress_lock_key = $addon->get_slug() . '_refresh_lock';
		$this->rate_limit_lock_key          = $addon->get_slug() . '_rate_limit';
	}

	/**
	 * Checks if the rate limit cache key is set, and sets the lock reason if locked.
	 *
	 * After consecutive failed requests, a cache key is set to prevent more failed requests.
	 *
	 * @sicne 2.2.0
	 *
	 * @return bool
	 */
	protected function is_rate_limited() {
		$rate_limited = \GFCache::get( $this->rate_limit_lock_key, $found );
		if ( $found && $rate_limited ) {
			$this->refresh_lock_reason = 'Refresh token request rate limit reached';

			return true;
		}

		return false;
	}

	/**
	 * Checks if the threshold for failed refresh requests has been reached, sets the cache key if so.
	 *
	 * @since 2.2.0
	 */
	public function increment_rate_limit() {
		$failed_requests_count = intval( get_option( self::FAILED_REQUESTS_COUNT_OPTION_KEY ) );
		if ( $failed_requests_count >= self::RATE_LIMIT_FAILED_REQUEST_THRESHOLD ) {
			$this->addon->log_debug( __METHOD__ . '(): Rate limit threshold reached, setting rate limit lock.' );
			\GFCache::set( $this->rate_limit_lock_key, true, true, self::RATE_LIMIT_CACHE_EXPIRATION_SECONDS );
			update_option( self::FAILED_REQUESTS_COUNT_OPTION_KEY, 0 );
		} else {
			$this->addon->log_debug( __METHOD__ . '(): Increasing failed requests count, current count: ' . $failed_requests_count );
			update_option( self::FAILED_REQUESTS_COUNT_OPTION_KEY, $failed_requests_count + 1 );
		}
	}

	/**
	 * Checks if there is a request already being made for refreshing the token.
	 *
	 * @since 2.2.0
	 *
	 * @return bool
	 */
	public function is_locked() {
		$locked = \GFCache::get( $this->refresh_in_progress_lock_key, $found );
		if ( $found && $locked ) {
			$this->refresh_lock_reason = 'Token Refresh is already in progress';

			return true;
		}

		return false;
	}

	/**
	 * Sets the refresh in progress cache lock.
	 *
	 * @since 2.2.0
	 */
	public function lock() {
		\GFCache::set( $this->refresh_in_progress_lock_key, true, true, self::REFRESH_IN_PROGRESS_EXPIRATION_SECONDS );
		$this->addon->log_debug( __METHOD__ . '(): Refresh in progress lock has been set' );
	}

	/**
	 * Clears the rate limit lock.
	 *
	 * @since 2.2.0
	 */
	public function reset_rate_limit() {
		\GFCache::delete( $this->rate_limit_lock_key );
		update_option( self::FAILED_REQUESTS_COUNT_OPTION_KEY, 0 );
		$this->addon->log_debug( __METHOD__ . '(): rate limit lock cleared.' );

	}

	/**
	 * Clears the refresh in progress lock.
	 *
	 * @since 2.2.0
	 */
	public function release_lock() {
		\GFCache::delete( $this->refresh_in_progress_lock_key );
		$this->addon->log_debug( __METHOD__ . '(): refresh in progress lock cleared.' );
	}

	/**
	 * Checks the token can be refreshed after making sure no locks are in place.
	 *
	 * @since 2.2.0
	 *
	 * @return bool
	 */
	public function can_refresh_token() {
		return $this->is_rate_limited() === false && $this->is_locked() === false;
	}
}

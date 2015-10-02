<?php
namespace LastFm\Src\Cache;

/** A cache policy.
 *
 * @package	LastFm API
 * @author Hemant Mann <hemant.mann121@gmail.com>
 * @version	1.0
 */
interface CachePolicy {
	/** Returns the expiration time by interpreting last.fm API request parameters.
	 *
	 * @param	array	$params	An associative array of last.fm API request parameters.
	 * @return	integer			Expiration time in seconds.
	 *
	 * @access	public
	 */
	public function getExpirationTime($params);
}



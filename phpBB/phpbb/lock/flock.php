<?php
/**
*
* This file is part of the phpBB Forum Software package.
*
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
* For full copyright and license information, please see
* the docs/CREDITS.txt file.
*
*/

namespace phpbb\lock;

/**
* File locking class
*/
class flock
{
	/**
	* Path to the file to which access is controlled
	*
	* @var string
	*/
	private $path;

	/**
	* File pointer for the lock file
	* @var resource|closed-resource|false
	*/
	private $lock_fp;

	/**
	* Constructor.
	*
	* You have to call acquire() to actually acquire the lock.
	*
	* @param	string	$path	Path to the file to which access is controlled
	*/
	public function __construct($path)
	{
		$this->path = $path;
		$this->lock_fp = false;
	}

	/**
	* Tries to acquire the lock.
	*
	* If the lock is already held by another process, this call will block
	* until the other process releases the lock. If a lock is acquired and
	* is not released before script finishes but the process continues to
	* live (apache/fastcgi) then subsequent processes trying to acquire
	* the same lock will be blocked forever.
	*
	* If the lock is already held by the same process via another instance
	* of this class, this call will block forever.
	*
	* If flock function is disabled in php or fails to work, lock
	* acquisition will fail and false will be returned.
	*
	* @return	bool			true if lock was acquired
	*							false otherwise
	*/
	public function acquire()
	{
		if ($this->lock_fp)
		{
			return false;
		}

		// For systems that can't have two processes opening
		// one file for writing simultaneously
		if (file_exists($this->path . '.lock'))
		{
			$mode = 'rb+';
		}
		else
		{
			$mode = 'wb';
		}

		$this->lock_fp = @fopen($this->path . '.lock', $mode);

		if ($mode == 'wb')
		{
			if (!$this->lock_fp)
			{
				// Two processes may attempt to create lock file at the same time.
				// Have the losing process try opening the lock file again for reading
				// on the assumption that the winning process created it
				$mode = 'rb+';
				$this->lock_fp = @fopen($this->path . '.lock', $mode);
			}
			else
			{
				// Only need to set mode when the lock file is written
				@chmod($this->path . '.lock', 0666);
			}
		}

		if ($this->lock_fp)
		{
			if (!@flock($this->lock_fp, LOCK_EX))
			{
				throw new \phpbb\exception\http_exception(500, 'Failure while aqcuiring locks.');
			}
		}

		return (bool) $this->lock_fp;
	}

	/**
	* Does this process own the lock?
	*
	* @return	bool			true if lock is owned
	*							false otherwise
	*/
	public function owns_lock()
	{
		return (bool) $this->lock_fp;
	}

	/**
	* Releases the lock.
	*
	* The lock must have been previously obtained, that is, acquire() call
	* was issued and returned true.
	*
	* Note: Attempting to release a lock that is already released,
	* that is, calling release() multiple times, is harmless.
	*
	* @return void
	*/
	public function release()
	{
		if ($this->lock_fp)
		{
			@flock($this->lock_fp, LOCK_UN);
			fclose($this->lock_fp);
			$this->lock_fp = false;
		}
	}
}

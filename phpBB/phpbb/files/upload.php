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

namespace phpbb\files;

use phpbb\language\language;
use phpbb\request\request;

/**
 * File upload class
 * Init class (all parameters optional and able to be set/overwritten separately) - scope is global and valid for all uploads
 */
class upload
{
	/** @var array Allowed file extensions */
	public $allowed_extensions = array();

	/** @var array Disallowed content */
	protected $disallowed_content = array('body', 'head', 'html', 'img', 'plaintext', 'a href', 'pre', 'script', 'table', 'title');

	/** @var int Maximum filesize */
	public $max_filesize = 0;

	/** @var int Minimum width of images */
	public $min_width = 0;

	/** @var int Minimum height of images */
	public $min_height = 0;

	/** @var int Maximum width of images */
	public $max_width = 0;

	/** @var int Maximum height of images */
	public $max_height = 0;

	/** @var string Prefix for language variables of errors */
	public $error_prefix = '';

	/** @var \phpbb\files\factory Files factory */
	protected $factory;

	/** @var \bantu\IniGetWrapper\IniGetWrapper ini_get() wrapper */
	protected $php_ini;

	/** @var language Language class */
	protected $language;

	/** @var request Request class */
	protected $request;

	/**
	 * Init file upload class.
	 *
	 * @param factory $factory Files factory
	 * @param language $language Language class
	 * @param \bantu\IniGetWrapper\IniGetWrapper $php_ini ini_get() wrapper
	 * @param request $request Request class
	 */
	public function __construct(factory $factory, language $language, \bantu\IniGetWrapper\IniGetWrapper $php_ini, request $request)
	{
		$this->factory = $factory;
		$this->language = $language;
		$this->php_ini = $php_ini;
		$this->request = $request;
	}

	/**
	 * Reset vars
	 */
	public function reset_vars()
	{
		$this->max_filesize = 0;
		$this->min_width = $this->min_height = $this->max_width = $this->max_height = 0;
		$this->error_prefix = '';
		$this->allowed_extensions = array();
		$this->disallowed_content = array();
	}

	/**
	 * Set allowed extensions
	 *
	 * @param array|bool $allowed_extensions Allowed file extensions
	 *
	 * @return \phpbb\files\upload This instance of upload
	 */
	public function set_allowed_extensions($allowed_extensions)
	{
		if ($allowed_extensions !== false)
		{
			$this->allowed_extensions = $allowed_extensions;
		}

		return $this;
	}

	/**
	 * Set allowed dimensions
	 *
	 * @param int $min_width Minimum image width
	 * @param int $min_height Minimum image height
	 * @param int $max_width Maximum image width
	 * @param int $max_height Maximum image height
	 *
	 * @return \phpbb\files\upload This instance of upload
	 */
	public function set_allowed_dimensions($min_width, $min_height, $max_width, $max_height)
	{
		$this->min_width = (int) $min_width;
		$this->min_height = (int) $min_height;
		$this->max_width = (int) $max_width;
		$this->max_height = (int) $max_height;

		return $this;
	}

	/**
	 * Set maximum allowed file size
	 *
	 * @param int $max_filesize Maximum file size
	 *
	 * @return \phpbb\files\upload This instance of upload
	 */
	public function set_max_filesize($max_filesize)
	{
		if ($max_filesize !== false && (int) $max_filesize)
		{
			$this->max_filesize = (int) $max_filesize;
		}

		return $this;
	}

	/**
	 * Set disallowed strings
	 *
	 * @param array|bool $disallowed_content Disallowed content
	 *
	 * @return \phpbb\files\upload This instance of upload
	 */
	public function set_disallowed_content($disallowed_content)
	{
		if ($disallowed_content !== false)
		{
			$this->disallowed_content = array_diff($disallowed_content, array(''));
		}

		return $this;
	}

	/**
	 * Set error prefix
	 *
	 * @param string $error_prefix Prefix for language variables of errors
	 *
	 * @return \phpbb\files\upload This instance of upload
	 */
	public function set_error_prefix($error_prefix)
	{
		$this->error_prefix = $error_prefix;

		return $this;
	}

	/**
	 * Handle upload based on type
	 *
	 * @param string $type Upload type
	 *
	 * @return \phpbb\files\filespec_storage|bool A filespec instance if upload was
	 *		successful, false if there were issues or the type is not supported
	 */
	public function handle_upload($type)
	{
		$args = func_get_args();
		array_shift($args);
		$type_class = $this->factory->get($type)
			->set_upload($this);

		return (is_object($type_class)) ? call_user_func_array(array($type_class, 'upload'), $args) : false;
	}

	/**
	 * Assign internal error
	 *
	 * @param string $errorcode Error code to assign
	 *
	 * @return string|false Error string or false if error code is not supported
	 * @access public
	 */
	public function assign_internal_error($errorcode)
	{
		switch ($errorcode)
		{
			case UPLOAD_ERR_INI_SIZE:
				$max_filesize = $this->php_ini->getString('upload_max_filesize');
				$unit = 'MB';

				if (!empty($max_filesize))
				{
					$unit = strtolower(substr($max_filesize, -1, 1));
					$max_filesize = (int) $max_filesize;

					$unit = ($unit == 'k') ? 'KB' : (($unit == 'g') ? 'GB' : 'MB');
				}

				$error = (empty($max_filesize)) ? $this->language->lang($this->error_prefix . 'PHP_SIZE_NA') : $this->language->lang($this->error_prefix . 'PHP_SIZE_OVERRUN', $max_filesize, $this->language->lang($unit));
			break;

			case UPLOAD_ERR_FORM_SIZE:
				$max_filesize = get_formatted_filesize($this->max_filesize, false);

				$error = $this->language->lang($this->error_prefix . 'WRONG_FILESIZE', $max_filesize['value'], $max_filesize['unit']);
			break;

			case UPLOAD_ERR_PARTIAL:
				$error = $this->language->lang($this->error_prefix . 'PARTIAL_UPLOAD');
			break;

			case UPLOAD_ERR_NO_FILE:
				$error = $this->language->lang($this->error_prefix . 'NOT_UPLOADED');
			break;

			case UPLOAD_ERR_NO_TMP_DIR:
			case UPLOAD_ERR_CANT_WRITE:
				$error = $this->language->lang($this->error_prefix . 'NO_TEMP_DIR');
			break;

			case UPLOAD_ERR_EXTENSION:
				$error = $this->language->lang($this->error_prefix . 'PHP_UPLOAD_STOPPED');
			break;

			default:
				$error = false;
			break;
		}

		return $error;
	}

	/**
	 * Perform common file checks
	 *
	 * @param filespec_storage|filespec $file Instance of filespec class
	 */
	public function common_checks($file)
	{
		// Filesize is too big or it's 0 if it was larger than the maxsize in the upload form
		if ($this->max_filesize && ($file->get('filesize') > $this->max_filesize || $file->get('filesize') == 0))
		{
			$max_filesize = get_formatted_filesize($this->max_filesize, false);

			$file->error[] = $this->language->lang($this->error_prefix . 'WRONG_FILESIZE', $max_filesize['value'], $max_filesize['unit']);
		}

		// check Filename
		if (preg_match("#[\\/:*?\"<>|]#i", $file->get('realname')))
		{
			$file->error[] = $this->language->lang($this->error_prefix . 'INVALID_FILENAME', $file->get('realname'));
		}

		// Invalid Extension
		if (!$this->valid_extension($file))
		{
			$file->error[] = $this->language->lang($this->error_prefix . 'DISALLOWED_EXTENSION', $file->get('extension'));
		}

		// MIME Sniffing
		if (!$this->valid_content($file))
		{
			$file->error[] = $this->language->lang($this->error_prefix . 'DISALLOWED_CONTENT');
		}
	}

	/**
	 * Check for allowed extension
	 *
	 * @param filespec_storage $file Instance of filespec class
	 *
	 * @return bool True if extension is allowed, false if not
	 */
	public function valid_extension($file)
	{
		return (in_array($file->get('extension'), $this->allowed_extensions)) ? true : false;
	}

	/**
	 * Check for allowed dimension
	 *
	 * @param filespec_storage|filespec $file Instance of filespec class
	 *
	 * @return bool True if dimensions are valid or no constraints set, false
	 *			if not
	 */
	public function valid_dimensions($file)
	{
		if (!$this->max_width && !$this->max_height && !$this->min_width && !$this->min_height)
		{
			return true;
		}

		if (($file->get('width') > $this->max_width && $this->max_width) ||
			($file->get('height') > $this->max_height && $this->max_height) ||
			($file->get('width') < $this->min_width && $this->min_width) ||
			($file->get('height') < $this->min_height && $this->min_height))
		{
			return false;
		}

		return true;
	}

	/**
	 * Check if form upload is valid
	 *
	 * @param string $form_name Name of form
	 *
	 * @return bool True if form upload is valid, false if not
	 */
	public function is_valid($form_name)
	{
		$upload = $this->request->file($form_name);

		return (!empty($upload) && $upload['name'] !== 'none');
	}


	/**
	 * Check for bad content (IE mime-sniffing)
	 *
	 * @param filespec_storage $file Instance of filespec class
	 *
	 * @return bool True if content is valid, false if not
	 */
	public function valid_content($file)
	{
		return ($file->check_content($this->disallowed_content));
	}

	/**
	 * Get image type/extension mapping
	 *
	 * @return array Array containing the image types and their extensions
	 */
	public static function image_types()
	{
		$result = [
			IMAGETYPE_GIF		=> ['gif'],
			IMAGETYPE_JPEG		=> ['jpg', 'jpeg'],
			IMAGETYPE_PNG		=> ['png'],
			IMAGETYPE_SWF		=> ['swf'],
			IMAGETYPE_PSD		=> ['psd'],
			IMAGETYPE_BMP		=> ['bmp'],
			IMAGETYPE_TIFF_II	=> ['tif', 'tiff'],
			IMAGETYPE_TIFF_MM	=> ['tif', 'tiff'],
			IMAGETYPE_JPC		=> ['jpg', 'jpeg'],
			IMAGETYPE_JP2		=> ['jpg', 'jpeg'],
			IMAGETYPE_JPX		=> ['jpg', 'jpeg'],
			IMAGETYPE_JB2		=> ['jpg', 'jpeg'],
			IMAGETYPE_IFF		=> ['iff'],
			IMAGETYPE_WBMP		=> ['wbmp'],
			IMAGETYPE_XBM		=> ['xbm'],
			IMAGETYPE_WEBP		=> ['webp'],
		];

		if (defined('IMAGETYPE_SWC'))
		{
			$result[IMAGETYPE_SWC] = ['swc'];
		}

		return $result;
	}
}

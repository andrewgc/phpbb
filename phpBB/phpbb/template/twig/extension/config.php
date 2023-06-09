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

namespace phpbb\template\twig\extension;

use Twig\Extension\AbstractExtension;

class config extends AbstractExtension
{
	/** @var \phpbb\config\config */
	protected $config;

	/**
	 * Constructor.
	 *
	 * @param \phpbb\config\config	$config		Configuration object
	 */
	public function __construct(\phpbb\config\config $config)
	{
		$this->config = $config;
	}

	/**
	 * Get the name of this extension
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'config';
	}

	/**
	 * Returns a list of global functions to add to the existing list.
	 *
	 * @return \Twig\TwigFunction[] An array of global functions
	 */
	public function getFunctions(): array
	{
		return array(
			new \Twig\TwigFunction('config', array($this, 'get_config')),
		);
	}

	/**
	 * Retrieves a configuration value for use in templates.
	 *
	 * @return int|string	The configuration value
	 */
	public function get_config()
	{
		$args = func_get_args();

		return $this->config->offsetGet($args[0]);
	}
}

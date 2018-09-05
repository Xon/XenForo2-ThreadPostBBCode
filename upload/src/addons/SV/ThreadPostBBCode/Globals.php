<?php

/*
 * This file is part of a XenForo add-on.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SV\ThreadPostBBCode;

/**
 * Add-on globals.
 */
class Globals
{
    /** @var bool[]  */
	public static $threadIds = [];

	/** @var bool[]  */
	public static $postIds = [];

	/** @var \XF\Mvc\Router */
	public static $router;

    /**
     * php-pm support, ensure globals are reset and the end of a request
     */
	public static function reset()
    {
        Globals::$threadIds = [];
        Globals::$postIds = [];
        Globals::$router = null;
    }

    /**
     * Private constructor, use statically.
     */
    private function __construct()
    {
    }
}

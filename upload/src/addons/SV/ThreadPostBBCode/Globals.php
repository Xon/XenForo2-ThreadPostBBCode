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

    /**
     * Private constructor, use statically.
     */
    private function __construct()
    {
    }
}

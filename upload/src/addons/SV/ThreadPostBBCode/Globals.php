<?php

namespace SV\ThreadPostBBCode;

/**
 * Add-on globals.
 */
abstract class Globals
{
    /** @var bool[] */
    public static $threadIds = [];

    /** @var bool[] */
    public static $postIds = [];

    /** @var \XF\Mvc\Router */
    public static $router;

    /**
     * php-pm support, ensure globals are reset and the end of a request
     */
    public static function reset(): void
    {
        Globals::$threadIds = [];
        Globals::$postIds = [];
        Globals::$router = null;
    }

    /**
     * Private constructor, use statically.
     */
    private function __construct() {}
}

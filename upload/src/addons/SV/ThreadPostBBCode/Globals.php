<?php

namespace SV\ThreadPostBBCode;

use XF\Mvc\Router;

/**
 * Add-on globals.
 */
abstract class Globals
{
    /** @var bool[] */
    public static $threadIds = [];

    /** @var bool[] */
    public static $postIds = [];

    /** @var Router */
    public static $router;

    /** @var array<int, bool> */
    public static $threadViewChecks;
    /** @var array<int, bool> */
    public static $postViewChecks;

    /**
     * php-pm support, ensure globals are reset and the end of a request
     */
    public static function reset(): void
    {
        Globals::$threadIds = [];
        Globals::$postIds = [];
        Globals::$router = null;
        Globals::$threadViewChecks = [];
        Globals::$postViewChecks = [];
    }

    /**
     * Private constructor, use statically.
     */
    private function __construct() {}
}

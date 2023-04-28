<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace SV\ThreadPostBBCode\XF\BbCode;

use SV\ThreadPostBBCode\Globals;
use SV\ThreadPostBBCode\Listener;

class RuleSet extends XFCP_RuleSet
{
    public function __construct($context, $subContext = null, $addDefault = true)
    {
        parent::__construct($context, $subContext, $addDefault);
        \XF::runOnce('bbcodeCleanup', function () {
            Globals::reset();
        });
    }

    public function getCustomTagConfig(array $tag)
    {
        $output = parent::getCustomTagConfig($tag);

        if (($tag['callback_class'] ?? '') === Listener::class && ($tag['callback_method'] ?? '') === 'renderBbCode')
        {
            $output['parseValidate'] = [$this, 'parseSvBbCodeThreadPost'];
        }

        return $output;
    }

    public function parseSvBbCodeThreadPost($tag, $option)
    {
        $id = (int)$option;
        if ($id === 0)
        {
            return false;
        }

        if ($tag === 'thread')
        {
            Globals::$threadIds[$id] = true;
        }
        else
        {
            Globals::$postIds[$id] = true;
        }
        return true;
    }
}

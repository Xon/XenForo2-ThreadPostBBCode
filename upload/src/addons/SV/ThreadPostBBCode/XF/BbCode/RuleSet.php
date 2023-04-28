<?php

namespace SV\ThreadPostBBCode\XF\BbCode;

use SV\ThreadPostBBCode\Globals;

class RuleSet extends XFCP_RuleSet
{
    public function __construct($context, $subContext = null, $addDefault = true)
    {
        parent::__construct($context, $subContext, $addDefault);
        \XF::runOnce('bbcodeCleanup', function () {
            Globals::reset();
        });
    }

    /** @noinspection PhpMissingReturnTypeInspection */
    public function validateTag($tag, $option = null, &$parsingModifiers = [], array $tagStack = [])
    {
        $validTag = parent::validateTag($tag, $option, $parsingModifiers, $tagStack);

        if ($validTag)
        {
            if ($tag === 'thread')
            {
                $option = (int)$option;
                Globals::$threadIds[$option] = true;
            }
            else if ($tag === 'post')
            {
                $option = (int)$option;
                Globals::$postIds[$option] = true;
            }
        }

        return $validTag;
    }
}

<?php

namespace SV\ThreadPostBBCode\XF\BbCode;

use SV\ThreadPostBBCode\Globals;

class RuleSet extends XFCP_RuleSet
{
    public function validateTag($tag, $option = null, &$parsingModifiers = [])
    {
        $validTag = parent::validateTag($tag, $option, $parsingModifiers);

        if ($validTag)
        {
            switch ($tag)
            {
                case 'thread':
                    Globals::$threadIds[$option] = true;
                    break;
                case 'post':
                    Globals::$postIds[$option] = true;
                    break;
            }
        }

        return $validTag;
    }
}

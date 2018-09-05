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
                    if ($option = intval($option))
                    {
                        Globals::$threadIds[$option] = true;
                    }
                    else
                    {
                        return false;
                    }
                    break;
                case 'post':
                    if ($option = intval($option))
                    {
                        Globals::$postIds[$option] = true;
                    }
                    else
                    {
                        return false;
                    }
                    break;
            }
        }

        return $validTag;
    }
}

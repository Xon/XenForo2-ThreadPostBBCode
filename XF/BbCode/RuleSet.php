<?php

namespace SV\ThreadPostBBCode\XF\BbCode;

use SV\ThreadPostBBCode\StaticGlobals;

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
					StaticGlobals::$threadIds[] = $option;
					break;
				case 'post':
					StaticGlobals::$postIds[] = $option;
					break;
			}
		}

		return $validTag;
	}
}
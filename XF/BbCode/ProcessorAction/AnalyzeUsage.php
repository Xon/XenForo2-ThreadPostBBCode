<?php

namespace SV\ThreadPostBBCode\XF\BbCode\ProcessorAction;

use SV\ThreadPostBBCode\StaticGlobals;
use XF\BbCode\ProcessorAction\AnalyzerHooks;

class AnalyzeUsage extends XFCP_AnalyzeUsage
{
	public function addAnalysisHooks(AnalyzerHooks $hooks)
	{
		parent::addAnalysisHooks($hooks);

		$hooks->addTagHook('thread', 'logLinkedThreadId');
		$hooks->addTagHook('post', 'logLinkedPostId');
	}

	public function logLinkedThreadId(array $tag, array $options, $finalOutput)
	{
		if ($tag['option'] && !in_array($tag['option'], StaticGlobals::$linkedThreadIds))
		{
			StaticGlobals::$linkedThreadIds[] = $tag['option'];
		}
	}

	public function logLinkedPostId(array $tag, array $options, $finalOutput)
	{
		if ($tag['option'] && !in_array($tag['option'], StaticGlobals::$linkedPostIds))
		{
			StaticGlobals::$linkedPostIds[] = $tag['option'];
		}
	}
}
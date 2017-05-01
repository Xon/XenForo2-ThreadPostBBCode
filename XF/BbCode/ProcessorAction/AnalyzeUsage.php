<?php

namespace SV\ThreadPostBBCode\XF\BbCode\ProcessorAction;

use XF\BbCode\ProcessorAction\AnalyzerHooks;

class AnalyzeUsage extends XFCP_AnalyzeUsage
{
	protected $linkedThreadIds = [];
	protected $linkedPostIds = [];

	public function addAnalysisHooks(AnalyzerHooks $hooks)
	{
		parent::addAnalysisHooks($hooks);

		$hooks->addTagHook('thread', 'logLinkedThreadId');
		$hooks->addTagHook('post', 'logLinkedPostId');
	}

	public function logLinkedThreadId(array $tag, array $options, $finalOutput)
	{
		if ($tag['option'])
		{
			$this->linkedThreadIds[] = $tag['option'];
		}
	}

	public function logLinkedPostId(array $tag, array $options, $finalOutput)
	{
		if ($tag['option'])
		{
			$this->linkedPostIds[] = $tag['option'];
		}
	}

	public function getLinkedThreadIds()
	{
		return array_unique($this->linkedThreadIds);
	}

	public function getLinkedPostIds()
	{
		return array_unique($this->linkedPostIds);
	}
}
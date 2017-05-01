<?php

namespace SV\ThreadPostBBCode\XF\Service\Message;

class Preparer extends XFCP_Preparer
{
	protected $linkedThreadIds = [];
	protected $linkedPostIds = [];

	public function prepare($message)
	{
		$response = parent::prepare($message);

		/** @var \SV\ThreadPostBBCode\XF\BbCode\ProcessorAction\AnalyzeUsage $usage */
		$usage = $this->bbCodeProcessor->getAnalyzer('usage');

		$this->linkedThreadIds = $usage->getLinkedThreadIds();
		$this->linkedPostIds = $usage->getLinkedPostIds();

		return $response;
	}

	public function getLinkedThreadIds()
	{
		return $this->linkedThreadIds;
	}

	public function getLinkedPostIds()
	{
		return $this->linkedPostIds;
	}
}
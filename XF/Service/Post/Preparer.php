<?php

namespace SV\ThreadPostBBCode\XF\Service\Post;

use SV\ThreadPostBBCode\Listener;

class Preparer extends XFCP_Preparer
{
	/** @var  \SV\ThreadPostBBCode\XF\Service\Message\Preparer */
	protected $messagePreparerHack;

	protected $linkedThreadIds = [];
	protected $linkedPostIds = [];

	/*public function setMessage($message, $format = true)
	{
		$message = parent::setMessage($message, $format);

		$this->linkedThreadIds = $this->messagePreparerHack->getLinkedThreadIds();
		$this->linkedPostIds = $this->messagePreparerHack->getLinkedPostIds();

		return $message;
	}*/

	protected function getMessagePreparer($format = true)
	{
		$preparer = parent::getMessagePreparer($format);

		// This is annoying
		$this->messagePreparerHack = $preparer;

		return $preparer;
	}

	public function afterInsert()
	{
		parent::afterInsert();

		$this->updateThreadPostBbCodeCache();
	}

	public function afterUpdate()
	{
		parent::afterUpdate();

		$this->updateThreadPostBbCodeCache();
	}

	protected function updateThreadPostBbCodeCache()
	{
		Listener::updateThreadBbCodeCache($this->linkedThreadIds, $this->linkedPostIds, $this->post);
	}
}
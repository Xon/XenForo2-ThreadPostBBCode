<?php

namespace SV\ThreadPostBBCode;

use XF\Entity\Post;
use XF\Entity\Thread;

class Listener
{
	protected static $postCache = null;
	protected static $threadCache = null;

	protected static function loadData()
	{
		$threadIds = StaticGlobals::$threadIds;
		$postIds = StaticGlobals::$postIds;

		self::$threadCache = \XF::finder('XF:Thread')->with('Forum')->whereIds($threadIds)->fetch();
		self::$postCache = \XF::finder('XF:Post')->with('Thread')->with('Thread.Forum')->whereIds($postIds)->fetch();
	}

	public static function getPosts()
	{
		if (self::$postCache === null)
		{
			self::loadData();
		}

		return self::$postCache;
	}

	public static function getThreads()
	{
		if (self::$threadCache === null)
		{
			self::loadData();
		}

		return self::$threadCache;
	}

	public static function bbcodeThread($tagChildren, $tagOption, $tag, array $options, \XF\BbCode\Renderer\AbstractRenderer $renderer)
	{
		$threadId = $tagOption;

		$cachedThreads = self::getThreads();

		if (isset($cachedThreads[$threadId]))
		{
			$thread = $cachedThreads[$threadId];
		}
		else
		{
			/** @var \XF\Entity\Thread $thread */
			$thread = \XF::finder('XF:Thread')->with('Forum')->whereId($threadId)->fetchOne();
			self::$threadCache[$thread->thread_id] = $thread;
		}

		if (!$thread || !$thread->canView())
		{
			return $renderer->renderSubTree($tagChildren, $options);
		}

		$link = \XF::app()->router()->buildLink('threads', $thread);

		return '<a href="' . $link . '" class="internalLink">' . $renderer->renderSubTree($tagChildren, $options) . '</a>';
	}

	public static function bbcodePost($tagChildren, $tagOption, $tag, array $options, \XF\BbCode\Renderer\AbstractRenderer $renderer)
	{
		$postId = $tagOption;

		$cachedPosts = self::getPosts();

		if (isset($cachedPosts[$postId]))
		{
			$post = $cachedPosts[$postId];
		}
		else
		{
			/** @var \XF\Entity\Post $post */
			$post = \XF::finder('XF:Post')->with('Thread')->with('Thread.Forum')->whereId($postId)->fetchOne();
			self::$postCache[$post->post_id] = $post;
		}

		if (!$post || !$post->canView())
		{
			return $renderer->renderSubTree($tagChildren, $options);
		}

		$link = \XF::app()->router()->buildLink('posts', $post);

		return '<a href="' . $link . '" class="internalLink">' . $renderer->renderSubTree($tagChildren, $options) . '</a>';
	}

	public static function positionToPage($position)
	{
		$messagesPerPage = null;
		if ($messagesPerPage == null)
		{
			$messagesPerPage = \XF::options()->messagesPerPage;
		}

		return floor($position / $messagesPerPage) + 1;
	}
}
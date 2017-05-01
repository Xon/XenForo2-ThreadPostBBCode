<?php

namespace SV\ThreadPostBBCode;

use XF\Entity\Post;
use XF\Entity\Thread;

class Listener
{
	protected static $cacheKeyBase = 'sv_postthreadmap_';
	protected static $cacheKey = null;

	protected static $postCache = null;
	protected static $threadCache = null;

	protected static function loadData()
	{
		$cacheDataRaw = \XF::app()->simpleCache()
			->getValue('SV/ThreadPostBBCode', self::$cacheKeyBase . self::$cacheKey);

		$cache = @unserialize($cacheDataRaw);

		if ($cache)
		{
			$threadIds = isset($cache['threadIds']) ? $cache['threadIds'] : [];
			$postIds = isset($cache['postIds']) ? $cache['postIds'] : [];

			self::$threadCache = \XF::finder('XF:Thread')->with('Forum')->whereIds($threadIds)->fetch();
			self::$postCache = \XF::finder('XF:Post')->with('Thread')->with('Thread.Forum')->whereIds($postIds)->fetch();
		}
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

	/**
	 * @param int $threadId
	 * @param int $page
	 *
	 * @internal param string $cacheKey
	 * @return string
	 */
	public static function getCacheKey($threadId, $page)
	{
		return self::$cacheKeyBase . 'thread_' . $threadId . '_page_' . $page;
	}

	public static function setCacheKey($threadId, $page)
	{
		self::$cacheKey = 'thread_' . $threadId . '_page_' . $page;
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

	public static function templatePreRender(\XF\Template\Templater $templater, &$type, &$template, array &$params)
	{
		if (isset($params['thread']))
		{
			self::setCacheKey($params['thread']->thread_id, self::positionToPage($params['thread']->position));
		}
	}

	public static function updateThreadBbCodeCache(array $newThreadIds, array $newPostIds, Post $post, Thread $thread = null)
	{
		//TODO Figure out how to handle post deletes...

		if ($thread === null)
		{
			$thread = $post->Thread;
		}

		$cacheKey = Listener::getCacheKey($thread->thread_id, Listener::positionToPage($post->position));
		$cache = \XF::app()->simpleCache();

		$existingCacheRaw = $cache->keyExists('SV/ThreadPostBBCode', $cacheKey) ? $cache->getValue('SV/ThreadPostBBCode', $cacheKey) : [];

		$cacheData = @unserialize($existingCacheRaw);

		if (!is_array($cacheData))
		{
			$cacheData = [
				'threadIds' => [],
				'postIds' => []
			];
		}

		$cacheData['threadIds'] = array_merge($cacheData['threadIds'], $newThreadIds);
		$cacheData['postIds'] = array_merge($cacheData['postIds'], $newPostIds);

		$cache
			->setValue('SV/ThreadPostBBCode', $cacheKey, serialize($cacheData));
	}
}
<?php

namespace SV\ThreadPostBBCode;

use XF\Entity\Post;
use XF\Entity\Thread;

class Listener
{
    protected static $loadedPostIds   = [];
    protected static $loadedThreadIds = [];
    protected static $loadedForumIds  = [];

    /**
     * @param string $shortname
     * @param array $ids
     */
    protected static function loadEntities($shortname, array $ids)
    {
        $em = \XF::em();
        $toLoad = [];
        foreach ($ids as $threadId => $null)
        {
            if ($em->findCached($shortname, $threadId))
            {
                $toLoad[] = $threadId;
            }
        }
        if ($toLoad)
        {
            \XF::finder($shortname)->whereIds($toLoad)->fetch();
        }
    }


    protected static function loadData()
    {
        $em = \XF::em();
        $toLoad = [];
        foreach (Globals::$postIds as $id => $null)
        {
            /** @var \XF\Entity\Post $post */
            if ($post = $em->findCached('XF:Post', $id))
            {
                Globals::$threadIds[$post->thread_id] = true;
            }
            else
            {
                $toLoad[] = $id;
            }
        }
        Globals::$postIds = [];
        if ($toLoad)
        {
            /** @var \XF\Entity\Post[] $entities */
            $entities = \XF::finder('XF:Post')->whereIds($toLoad)->fetch();
            foreach ($entities as $entity)
            {
                Globals::$threadIds[$entity->thread_id] = true;
            }
        }

        $forumIds = [];

        $em = \XF::em();
        $toLoad = [];
        foreach (Globals::$threadIds as $id => $null)
        {
            /** @var \XF\Entity\Thread $thread */
            if ($thread = $em->findCached('XF:Thread', $id))
            {
                $forumIds[$thread->node_id] = true;
            }
            else
            {
                $toLoad[] = $id;
            }
        }
        Globals::$threadIds = [];
        if ($toLoad)
        {
            /** @var \XF\Entity\Thread[] $entities */
            $entities = \XF::finder('XF:Thread')->whereIds($toLoad)->fetch();
            foreach ($entities as $entity)
            {
                $forumIds[$entity->node_id] = true;
            }
        }


        $em = \XF::em();
        $toLoad = [];
        foreach ($forumIds as $id => $null)
        {
            /** @var \XF\Entity\Forum $forum */
            if (!$em->findCached('XF:Forum', $id))
            {
                $toLoad[] = $id;
            }
        }
        if ($toLoad)
        {
            \XF::finder('XF:Forum')->whereIds($toLoad)->fetch();
        }
    }

    public static function bbcodeThread($tagChildren, $tagOption, $tag, array $options, \XF\BbCode\Renderer\AbstractRenderer $renderer)
    {
        self::loadData();

        $threadId = $tagOption;
        /** @var \XF\Entity\Thread $thread */
        $thread = \XF::em()->findCached('XF:Thread', $threadId);

        if (!$thread || !$thread->canView())
        {
            $link = \XF::app()->router()->buildLink('threads', ['thread_id' => $threadId]);
        }
        else
        {
            $link = \XF::app()->router()->buildLink('threads', $thread);
        }

        return '<a href="' . $link . '" class="internalLink">' . $renderer->renderSubTree($tagChildren, $options) . '</a>';
    }

    public static function bbcodePost($tagChildren, $tagOption, $tag, array $options, \XF\BbCode\Renderer\AbstractRenderer $renderer)
    {
        self::loadData();

        $postId = $tagOption;
        /** @var \XF\Entity\Post $post */
        $post = \XF::em()->findCached('XF:Post', $postId);

        if (!$post || !$post->canView())
        {
            $link = \XF::app()->router()->buildLink('posts', ['post_id' => $postId]);
        }
        else
        {
            $link = \XF::app()->router()->buildLink('threads/post', $post->Thread, ['post_id' => $post->post_id]);
        }

        return '<a href="' . $link . '" class="internalLink">' . $renderer->renderSubTree($tagChildren, $options) . '</a>';
    }
}

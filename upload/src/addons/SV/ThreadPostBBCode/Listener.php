<?php

namespace SV\ThreadPostBBCode;

use XF\BbCode\Renderer\AbstractRenderer;

class Listener
{
    protected static function loadData()
    {
        if (Globals::$router === null)
        {
            Globals::$router = \XF::app()->router('public');
        }

        if (!Globals::$threadIds && !Globals::$postIds)
        {
            return;
        }

        \XF::runOnce('bbcodeCleanup', function () {
            Globals::reset();
        });
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

    public static function renderBbCode($tagChildren, $tagOption, $tag, array $options, AbstractRenderer $renderer)
    {
        self::loadData();

        $id = intval($tagOption);
        if (!$id)
        {
            return $renderer->renderUnparsedTag($tag, $options);
        }

        $tagName = $tag['tag'];
        if ($tagName === 'thread')
        {
            /** @var \XF\Entity\Thread $thread */
            $thread = \XF::em()->findCached('XF:Thread', $id);
            if (!$thread || !$thread->canView())
            {
                $link = Globals::$router->buildLink('threads', ['thread_id' => $id]);
            }
            else
            {
                $link = Globals::$router->buildLink('threads', $thread);
            }
        }
        else if ($tagName === 'post')
        {
            /** @var \XF\Entity\Post $post */
            $post = \XF::em()->findCached('XF:Post', $id);
            if (!$post || !$post->canView())
            {
                $link = Globals::$router->buildLink('posts', ['post_id' => $id]);
            }
            else
            {
                if ($thread = $post->Thread)
                {
                    $page = floor($post->position / \XF::options()->messagesPerPage) + 1;

                    $link = Globals::$router->buildLink('threads', $thread, ['page' => $page]) . '#post-' . $post->post_id;
                }
                else
                {
                    $link = Globals::$router->buildLink('posts', $post);
                }
            }
        }
        else
        {
            return $renderer->renderUnparsedTag($tag, $options);
        }

        $children = $renderer->renderSubTree($tagChildren, $options);
        // using the body as id, so render a full URL to display in the thread instead
        if (empty($children))
        {
            $children = $link;
        }

        if ($renderer instanceof \XF\BbCode\Renderer\SimpleHtml || $renderer instanceof \XF\BbCode\Renderer\EmailHtml)
        {
            return '<a href="' . htmlspecialchars($link) . '">' . $children . '</a>';
        }

        $linkInfo = \XF::app()->stringFormatter()->getLinkClassTarget($link);

        $classAttr = $linkInfo['class'] ? "class=\"$linkInfo[class]\"" : '';
        $targetAttr = $linkInfo['target'] ? "target=\"$linkInfo[target]\"" : '';

        return '<a href="' . htmlspecialchars($link) . '"' . $targetAttr . $classAttr . '>' . $children . '</a>';
    }
}

<?php

namespace SV\ThreadPostBBCode;

use XF\BbCode\Renderer\AbstractRenderer;
use XF\Entity\Forum;
use XF\Entity\Post;
use XF\Entity\Thread;
use function count;
use function floor;

abstract class Listener
{
    /**
     * Private constructor, use statically.
     */
    private function __construct() {}

    protected static function loadData(): void
    {
        if (Globals::$router === null)
        {
            Globals::$router = \XF::app()->router('public');
        }

        if (count(Globals::$threadIds) === 0 && count(Globals::$postIds) === 0)
        {
            return;
        }

        \XF::runOnce('bbcodeCleanup', function () {
            Globals::reset();
        });

        $threadViewChecks = &Globals::$threadViewChecks;
        $postViewChecks = &Globals::$postViewChecks;

        $threadsToCheck = [];
        $postsToCheck = [];
        $em = \XF::em();
        $toLoad = [];
        foreach (Globals::$postIds as $id => $null)
        {
            /** @var Post $post */
            $post = $em->findCached('XF:Post', $id);
            if ($post)
            {
                Globals::$threadIds[$post->thread_id] = true;
                $postsToCheck[$id] = $post;
            }
            else
            {
                $toLoad[] = $id;
            }
        }
        Globals::$postIds = [];
        if ($toLoad)
        {
            /** @var Post[] $entities */
            $entities = \XF::finder('XF:Post')->whereIds($toLoad)->fetch();
            foreach ($entities as $id => $post)
            {
                Globals::$threadIds[$post->thread_id] = true;
                $postsToCheck[$id] = $post;
            }
        }

        $forumIds = [];

        $em = \XF::em();
        $toLoad = [];
        foreach (Globals::$threadIds as $id => $null)
        {
            /** @var Thread $thread */
            $thread = $em->findCached('XF:Thread', $id);
            if ($thread)
            {
                $forumIds[$thread->node_id] = true;
                $threadsToCheck[$id] = $thread;
            }
            else
            {
                $toLoad[] = $id;
            }
        }

        Globals::$threadIds = [];
        if ($toLoad)
        {
            /** @var Thread[] $entities */
            $entities = \XF::finder('XF:Thread')->whereIds($toLoad)->fetch();
            foreach ($entities as $id => $thread)
            {
                $forumIds[$thread->node_id] = true;
                $threadsToCheck[$id] = $thread;
            }
        }

        $em = \XF::em();
        $toLoad = [];
        foreach ($forumIds as $id => $null)
        {
            /** @var Forum $forum */
            if (!$em->findCached('XF:Forum', $id))
            {
                $toLoad[] = $id;
            }
        }
        if ($toLoad)
        {
            \XF::finder('XF:Forum')->whereIds($toLoad)->fetch();
        }

        // do thread view checks
        foreach ($threadsToCheck as $id => $thread)
        {
            $threadViewChecks[$id] = $thread && $thread->canView();
        }

        // post checks
        foreach ($postsToCheck as $id => $post)
        {
            $postViewChecks[$id] = !empty($threadViewChecks[$post->thread_id]) && ($post->message_state === 'visible' || $post->canView());
        }
    }

    public static function renderBbCode($tagChildren, string $tagOption, array $tag, array $options, AbstractRenderer $renderer): string
    {
        self::loadData();

        $id = (int)$tagOption;
        if ($id === 0)
        {
            return $renderer->renderUnparsedTag($tag, $options);
        }

        $tagName = $tag['tag'];
        if ($tagName === 'thread')
        {
            if (!(Globals::$threadViewChecks[$id] ?? false))
            {
                $link = Globals::$router->buildLink('canonical:threads', ['thread_id' => $id]);
            }
            else
            {
                /** @var Thread $thread */
                $thread = \XF::em()->findCached('XF:Thread', $id);
                if (!$thread)
                {
                    $link = Globals::$router->buildLink('canonical:threads', ['thread_id' => $id]);
                }
                else
                {
                    $link = Globals::$router->buildLink('canonical:threads', $thread);
                }
            }
        }
        else if ($tagName === 'post')
        {
            if (!(Globals::$postViewChecks[$id] ?? false))
            {
                $link = Globals::$router->buildLink('canonical:posts', ['post_id' => $id]);
            }
            else
            {
                /** @var Post $post */
                $post = \XF::em()->findCached('XF:Post', $id);
                if (!$post)
                {
                    $link = Globals::$router->buildLink('canonical:posts', ['post_id' => $id]);
                }
                else
                {
                    if ($thread = $post->Thread)
                    {
                        $page = floor($post->position / \XF::options()->messagesPerPage) + 1;

                        $link = Globals::$router->buildLink('canonical:threads', $thread, ['page' => $page]) . '#post-' . $post->post_id;
                    }
                    else
                    {
                        $link = Globals::$router->buildLink('canonical:posts', $post);
                    }
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

        return '<a href="' . htmlspecialchars($link) . '" ' . $targetAttr . $classAttr . ' >' . $children . '</a>';
    }
}

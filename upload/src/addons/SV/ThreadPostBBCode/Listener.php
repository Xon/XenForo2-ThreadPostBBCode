<?php

namespace SV\ThreadPostBBCode;

use SV\StandardLib\Helper;
use XF\BbCode\Renderer\AbstractRenderer;
use XF\BbCode\Renderer\EmailHtml as EmailHtmlRenderer;
use XF\BbCode\Renderer\SimpleHtml as SimpleHtmlRenderer;
use XF\Entity\Forum as ForumEntity;
use XF\Entity\Post as PostEntity;
use XF\Entity\Thread as ThreadEntity;
use function count;
use function floor;
use function htmlspecialchars;

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
        $toLoad = [];
        foreach (Globals::$postIds as $id => $null)
        {
            $post = Helper::findCached(PostEntity::class, $id);
            if ($post !== null)
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
            $entities = Helper::findByIds(PostEntity::class, $toLoad);
            /**
             * @var int $id
             * @var PostEntity $post
             */
            foreach ($entities as $id => $post)
            {
                Globals::$threadIds[$post->thread_id] = true;
                $postsToCheck[$id] = $post;
            }
        }

        $forumIds = [];
        $toLoad = [];
        foreach (Globals::$threadIds as $id => $null)
        {
            $thread = Helper::findCached(ThreadEntity::class, $id);
            if ($thread !== null)
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
            $entities = Helper::findByIds(ThreadEntity::class, $toLoad);
            /**
             * @var int $id
             * @var ThreadEntity $thread
             */
            foreach ($entities as $id => $thread)
            {
                $forumIds[$thread->node_id] = true;
                $threadsToCheck[$id] = $thread;
            }
        }

        $toLoad = [];
        foreach ($forumIds as $id => $null)
        {
            $forum = Helper::findCached(ForumEntity::class, $id);
            if ($forum !== null)
            {
                $toLoad[] = $id;
            }
        }
        if ($toLoad)
        {
            Helper::findByIds(ForumEntity::class, $toLoad);
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
                $thread = Helper::findCached(ThreadEntity::class, $id);
                if ($thread === null)
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
                $post = Helper::findCached(PostEntity::class, $id);
                if ($post === null)
                {
                    $link = Globals::$router->buildLink('canonical:posts', ['post_id' => $id]);
                }
                else
                {
                    $thread = $post->Thread;
                    if ($thread !== null)
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

        if ($renderer instanceof SimpleHtmlRenderer || $renderer instanceof EmailHtmlRenderer)
        {
            return '<a href="' . htmlspecialchars($link) . '">' . $children . '</a>';
        }

        $linkInfo = \XF::app()->stringFormatter()->getLinkClassTarget($link);

        $classAttr = $linkInfo['class'] ? 'class="' . $linkInfo['class'] . '"' : '';
        $targetAttr = $linkInfo['target'] ? 'target="' . $linkInfo['target'] . '"' : '';

        return '<a href="' . htmlspecialchars($link) . '" ' . $targetAttr . $classAttr . ' >' . $children . '</a>';
    }
}

<?php

namespace GitHubSummary\Helpers;

use \DateTime;

class EventBuilder
{

    static public function build($event)
    {
        $method = 'build' . $event->type;
        $response = array('created_at' => \DateTime::createFromFormat(DateTime::ISO8601, $event->created_at)->format('U'));

        if (method_exists(__CLASS__, $method))
            $result = $response + call_user_func(array(__CLASS__, $method), $event);
        else
            $result = $response + self::buildNotImplementYet($event);

        $result['id'] = md5(serialize($result));
        return $result;
    }

    static public function buildCommitCommentEvent($event)
    {
        return array(
            'actor' => $event->repo->name,
            'message' => sprintf('<a href="https://github.com/%s">%s</a> commented on commit <a href="%s">%s</a>', $event->actor->login, $event->actor->login, $event->payload->comment->html_url, $event->payload->comment->commit_id),
            'extra' => $event->payload->comment->body
        );
    }

    static public function buildCreateEvent($event)
    {
        if(empty($event->payload->ref))
            return array(
                'actor' => $event->actor->login,
                'message' => sprintf('created %s <a href="https://github.com/%s">%s</a>', $event->payload->ref_type, $event->repo->name, $event->repo->name),
                'extra' => $event->payload->description
            );
        else
            return array(
                'actor' => $event->repo->name,
                'message' => sprintf('<a href="https://github.com/%s">%s</a> created %s <a href="https://github.com/%s/tree/%s">%s</a>', $event->actor->login, $event->actor->login, $event->payload->ref_type, $event->repo->name, $event->payload->ref, $event->payload->ref),
                'extra' => $event->payload->description
            );
    }

    static public function buildDeleteEvent($event)
    {
        return array(
            'actor' => $event->repo->name,
            'message' => sprintf('<a href="https://github.com/%s">%s</a> deleted %s %s', $event->actor->login, $event->actor->login, $event->payload->ref_type, $event->payload->ref),
            'extra' => NULL
        );
    }

    //    DownloadEvent

    static public function buildFollowEvent($event)
    {
        return array(
            'actor' => $event->actor->login,
            'message' => sprintf('started following <a href="https://github.com/%s">%s</a>', $event->payload->target->login, $event->payload->target->login),
            'extra' => NULL
        );
    }

    static public function buildForkEvent($event)
    {
        return array(
            'actor' => $event->actor->login,
            'message' => sprintf('forked <a href="https://github.com/%s">%s</a>', $event->repo->name, $event->repo->name),
            'extra' => NULL
        );
    }

    //    ForkApplyEvent

    static public function buildGistEvent($event)
    {
        return array(
            'actor' => $event->actor->login,
            'message' => sprintf('%sd gist <a href="%s">%d</a> %s', $event->payload->action, $event->payload->gist->html_url, $event->payload->gist->id, $event->payload->gist->description),
            'extra' => NULL
        );
    }

    static public function buildGollumEvent($event)
    {
        return array(
            'actor' => $event->repo->name,
            'message' => sprintf('<a href="https://github.com/%s">%s</a> %s wiki page: <a href="%s">%s</a>', $event->actor->login, $event->actor->login, $event->payload->pages[0]->action, $event->payload->pages[0]->html_url, $event->payload->pages[0]->page_name),
            'extra' => NULL
        );
    }

    static public function buildIssueCommentEvent($event)
    {
        return array(
            'actor' => $event->repo->name,
            'message' => sprintf('<a href="https://github.com/%s">%s</a> commented on issue <a href="%s">%s</a>', $event->actor->login, $event->actor->login, $event->payload->issue->html_url, $event->payload->issue->title),
            'extra' => $event->payload->comment->body
        );
    }

    static public function buildIssuesEvent($event)
    {
        return array(
            'actor' => $event->repo->name,
            'message' => sprintf('<a href="https://github.com/%s">%s</a> %s issue <a href="%s">%s</a>', $event->actor->login, $event->actor->login, $event->payload->action, $event->payload->issue->html_url, $event->payload->issue->title),
            'extra' => NULL
        );
    }

    static public function buildMemberEvent($event)
    {
        return array(
            'actor' => $event->actor->login,
            'message' => sprintf('%s <a href="https://github.com/%s">%s</a> on <a href="%s">%s</a>', $event->payload->action, $event->payload->member->login, $event->payload->member->login, $event->repo->name, $event->repo->name),
            'extra' => NULL
        );
    }

    //    PublicEvent

    static public function buildPullRequestEvent($event)
    {
        return array(
            'actor' => $event->repo->name,
            'message' => sprintf('<a href="%s">pull request</a> from <a href="https://github.com/%s">%s</a>', $event->payload->pull_request->html_url, $event->actor->login, $event->actor->login),
            'extra' => $event->payload->pull_request->title
        );
    }

    static public function buildPullRequestReviewCommentEvent($event)
    {
        return array(
            'actor' => $event->repo->name,
            'message' => sprintf('<a href="https://github.com/%s">%s</a> commented on pull request <a href="https://github.com/%s/pulls">%s</a>', $event->actor->login, $event->actor->login, $event->repo->name, $event->payload->comment->path),
            'extra' => $event->payload->comment->body
        );
    }

    static public function buildPushEvent($event)
    {
        $messages = '';
        foreach($event->payload->commits as $commit)
            $messages .= sprintf('<li>%s</li>', $commit->message);

        return array(
            'actor' => $event->repo->name,
            'message' => sprintf('<a href="https://github.com/%s">%s</a> pushed to %s <a href="https://github.com/%s">%s</a>', $event->actor->login, $event->actor->login,
                    end(explode('/',$event->payload->ref)), $event->repo->name, $event->repo->name),
            'extra' => sprintf('<ul>%s</ul>', $messages)
        );
    }

    //    TeamAddEvent

    static public function buildWatchEvent($event)
    {
        return array(
            'actor' => $event->actor->login,
            'message' => sprintf('started watching <a href="https://github.com/%s">%s</a>', $event->repo->name, $event->repo->name),
            'extra' => NULL
        );
    }

    static public function buildNotImplementYet($event)
    {
        return array(
            'actor' => $event->type,
            'message' => 'notImplementedYet',
            'extra' => serialize($event)
        );
    }

}

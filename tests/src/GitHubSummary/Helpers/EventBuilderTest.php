<?php

namespace GitHubSummary\Helpers;

require_once dirname(__FILE__) . '/../../../../src/GitHubSummary/Helpers/EventBuilder.php';

class EventBuilderTest extends \PHPUnit_Framework_TestCase
{

    public function testBuild()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testBuildCommitCommentEvent()
    {
        $event = json_decode('{
            "payload":{
                "comment":{
                    "body":"my message",
                    "commit_id":"123456",
                    "html_url":"https:\/\/github.com\/user\/repo\/commit\/123456"
                }
            },
            "repo":{
                "name":"user\/repo"
            },
            "actor":{
                "login":"actor"
            }
        }');

        $expected = array(
            'actor' => 'user/repo',
            'message' => '<a href="https://github.com/actor">actor</a> commented on commit <a href="https://github.com/user/repo/commit/123456">123456</a>',
            'extra' => 'my message'
        );

        $this->assertEquals($expected, EventBuilder::buildCommitCommentEvent($event));
    }

    public function testBuildCreateEvent()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testBuildDeleteEvent()
    {
        $event = json_decode('{
            "payload":{
                "ref_type":"tag",
                "ref":"123456"
            },
            "repo":{
                "name":"user\/repo"
            },
            "actor":{
                "login":"actor"
            }
        }');

        $expected = array(
            'actor' => 'user/repo',
            'message' => '<a href="https://github.com/actor">actor</a> deleted tag 123456',
            'extra' => NULL
        );

        $this->assertEquals($expected, EventBuilder::BuildDeleteEvent($event));
    }

    public function testBuildFollowEvent()
    {
        $event = json_decode('{
            "payload":{
                "target":{
                    "login":"user"
                }
            },
            "actor":{
                "login":"actor"
            }
        }');

        $expected = array(
            'actor' => 'actor',
            'message' => 'started following <a href="https://github.com/user">user</a>',
            'extra' => NULL
        );

        $this->assertEquals($expected, EventBuilder::BuildFollowEvent($event));
    }

    public function testBuildForkEvent()
    {
        $event = json_decode('{
            "repo":{
                "name":"user\/repo"
            },
            "actor":{
                "login":"actor"
            }
        }');

        $expected = array(
            'actor' => 'actor',
            'message' => 'forked <a href="https://github.com/user/repo">user/repo</a>',
            'extra' => NULL
        );

        $this->assertEquals($expected, EventBuilder::BuildForkEvent($event));
    }

    public function testBuildGistEvent()
    {
        $event = json_decode('{
            "payload":{
                "action":"create",
                "gist":{
                    "html_url":"https:\/\/gist.github.com\/123456",
                    "id":"123456",
                    "description":"my gist"
                }
            },
            "actor":{
                "login":"actor"
            }
        }');

        $expected = array(
            'actor' => 'actor',
            'message' => 'created gist <a href="https://gist.github.com/123456">123456</a> my gist',
            'extra' => NULL
        );

        $this->assertEquals($expected, EventBuilder::BuildGistEvent($event));
    }

    public function testBuildGollumEvent()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testBuildIssueCommentEvent()
    {
        $event = json_decode('{
            "payload":{
                "comment":{
                    "body":"my issue comment"
                },
                "issue":{
                    "html_url":"https:\/\/github.com\/user\/repo\/issues\/123456",
                    "title":"issue\'s title"
                }
            },
            "repo":{
                "name":"user\/repo"
            },
            "actor":{
                "login":"actor"
            }
        }');

        $expected = array(
            'actor' => 'user/repo',
            'message' => '<a href="https://github.com/actor">actor</a> commented on issue <a href="https://github.com/user/repo/issues/123456">issue\'s title</a>',
            'extra' => 'my issue comment'
        );

        $this->assertEquals($expected, EventBuilder::BuildIssueCommentEvent($event));
    }

    public function testBuildIssuesEvent()
    {
        $event = json_decode('{
            "payload":{
                "action":"opened",
                "comment":{
                    "body":"my issue comment"
                },
                "issue":{
                    "html_url":"https:\/\/github.com\/user\/repo\/issues\/123456",
                    "title":"issue\'s title"
                }
            },
            "repo":{
                "name":"user\/repo"
            },
            "actor":{
                "login":"actor"
            }
        }');

        $expected = array(
            'actor' => 'user/repo',
            'message' => '<a href="https://github.com/actor">actor</a> opened issue <a href="https://github.com/user/repo/issues/123456">issue\'s title</a>',
            'extra' => NULL
        );

        $this->assertEquals($expected, EventBuilder::BuildIssuesEvent($event));
    }

    public function testBuildMemberEvent()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testBuildPullRequestEvent()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testBuildPullRequestReviewCommentEvent()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testBuildPushEvent()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testBuildWatchEvent()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testBuildNotImplementYet()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

}

?>

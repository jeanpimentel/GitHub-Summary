<?php

namespace GitHubSummary\Services;

use GitHubSummary\Helpers\Cache;
use GitHubSummary\Helpers\EventBuilder;
use \DateTime;
use Respect\Relational\Mapper;
use Respect\Relational\Sql;

class GitHub
{

    private $cache;
    private $mapper;
    private $accessToken;

    function __construct(Cache $cache, Mapper $mapper, $accessToken = NULL)
    {
        $this->cache = $cache;
        $this->mapper = $mapper;
        $this->accessToken = $accessToken;
    }

    public function getAuthorizeUrl($clientId)
    {
        return 'https://github.com/login/oauth/authorize?client_id=' . $clientId;
    }

    public function getAccessToken($clientId, $clientSecret, $authorizeCode)
    {

        $data = array(
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $authorizeCode,
        );

        $response = $this->request('https://github.com/login/oauth/access_token', 'POST', $data);
        preg_match('/access_token=([0-9a-f]+)/', $response, $out);

        if (isset($out[1]))
        {
            $this->accessToken = $out[1];
            return $out[1];
        }

        return FALSE;
    }

    public function getWatchedRepositories()
    {
        return $this->mapper->repository->fetchAll(Sql::orderBy('updated_at DESC'));
    }

    public function getFollowingUsers()
    {
        return $this->mapper->user->fetchAll(Sql::orderBy('LOWER(login) ASC'));
    }

    public function getUser($login = NULL)
    {
        if (!is_null($login) && $this->cache->has('user' . $login))
            return json_decode($this->cache->get('user' . $login));

        $response = $this->request('https://api.github.com/user' . (!is_null($login) ? 's/' . $login : NULL));

        $user = json_decode($response);
        $data = array(
            'id' => $user->id,
            'login' => $user->login,
            'avatar' => isset($user->gravatar_id) ? 'http://www.gravatar.com/avatar/' . $user->gravatar_id : $user->avatar_url,
            'name' => isset($user->name) ? $user->name : NULL,
            'email' => isset($user->email) ? $user->email : NULL,
            'location' => isset($user->location) ? $user->location : NULL,
            'url' => $user->html_url,
        );

        if (!is_null($login))
            $this->cache->set('user' . $login, json_encode($data));

        return $data;
    }

    public function getUsersEvents()
    {
        $results = $this->mapper->event_user->fetchAll();
        return ($results) ? $this->groupEvents($results) : false;
    }
    
    public function getRepositoriesEvents()
    {
        $results = $this->mapper->event_repository->fetchAll();
        return ($results) ? $this->groupEvents($results) : false;
    }

    protected function groupEvents(array $results)
    {
        $data = array();
        foreach ($results as $result)
        {
            if (!isset($data[$result->actor]))
                $data[$result->actor] = array();

            $data[$result->actor][] = $result;
        }

        return $data;
    }

    private function request($url, $method = 'GET', $data = array())
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: token " . $this->accessToken));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'GET')
        {
            curl_setopt($curl, CURLOPT_URL, $url . (strpos($url, '?') === FALSE ? '?' : '') . http_build_query($data));
        }
        elseif ($method == 'POST')
        {
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        return curl_exec($curl);
    }

    private function updateFollowingUsers()
    {
        $dt = new \DateTime();
        $timestamp = $dt->format('U');

        $response = $this->request('https://api.github.com/user/following');
        foreach (json_decode($response) as $user)
        {

            $user = $this->simplifyObject($this->getUser($user->login), array('id', 'login', 'avatar', 'name'));
            $user->cron_at = $timestamp;

            if ($this->mapper->user[$user->id]->fetch())
                $this->mapper->markTracked($user, 'user', $user->id);

            $this->mapper->user->persist($user);
        }
        $this->mapper->flush();
    }

    private function updateWatchedRepositories()
    {
        $dt = new \DateTime();
        $timestamp = $dt->format('U');

        $response = $this->request('https://api.github.com/user/watched');
        foreach (json_decode($response) as $repository)
        {

            $repository = (object) array(
                        'id' => $repository->id,
                        'name' => $repository->owner->login . '/' . $repository->name,
                        'description' => $repository->description,
                        'url' => $repository->html_url,
                        'updated_at' => \DateTime::createFromFormat(DateTime::ISO8601, $repository->updated_at)->format('U'),
                        'cron_at' => $timestamp
            );

            if ($this->mapper->repository[$repository->id]->fetch())
                $this->mapper->markTracked($repository, 'repository', $repository->id);

            $this->mapper->repository->persist($repository);
        }
        $this->mapper->flush();
    }

    private function updateEvents($login)
    {
        $response = array();
        for ($i = 1; $i <= 8; $i++)
            $response = array_merge($response, json_decode($this->request('https://api.github.com/users/' . $login . '/received_events', 'GET', array('page' => $i))));

        foreach ($response as $event)
        {
            $event = (object) EventBuilder::build($event);

            if ($this->mapper->event[$event->id]->fetch())
                $this->mapper->markTracked($event, 'event', $event->id);

            $this->mapper->event->persist($event);
        }
        $this->mapper->flush();
    }

    public function update($login)
    {
        $this->updateFollowingUsers();
        $this->updateWatchedRepositories();
        $this->updateEvents($login);
    }

    private function simplifyObject($object, $fieldsToPersist)
    {
        foreach (array_keys(get_object_vars($object)) as $key)
            if (!in_array($key, $fieldsToPersist))
                unset($object->{$key});

        return $object;
    }

}

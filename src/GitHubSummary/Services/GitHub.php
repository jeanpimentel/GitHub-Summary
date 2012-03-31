<?php

namespace GitHubSummary\Services;

use GitHubSummary\Helpers\Cache;
use GitHubSummary\Helpers\EventBuilder;
use \DateTime;

class GitHub
{

    private $accessToken;
    private $cache;

    function __construct(Cache $cache, $accessToken = NULL)
    {
        $this->cache        = $cache;
        $this->accessToken  = $accessToken;
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
        if ($this->cache->has('watched' . $this->accessToken))
            return json_decode($this->cache->get('watched' . $this->accessToken));

        $response = $this->request('https://api.github.com/user/watched');

        $data = array();
        foreach (json_decode($response) as $repository)
            $data[] = array(
                'id' => $repository->id,
                'name' => $repository->owner->login . '/' . $repository->name,
                'description' => $repository->description,
                'url' => $repository->html_url,
                'updated_at' => \DateTime::createFromFormat(DateTime::ISO8601, $repository->updated_at)->format('U'),
            );

        $this->cache->set('watched' . $this->accessToken, json_encode($data));
        return $data;
    }

    public function getFollowingUsers()
    {
        if ($this->cache->has('following' . $this->accessToken))
            return json_decode($this->cache->get('following' . $this->accessToken));

        $response = $this->request('https://api.github.com/user/following');

        $data = array();
        foreach (json_decode($response) as $user)
            $data[] = $this->getUser($user->login);

        $this->cache->set('following' . $this->accessToken, json_encode($data));
        return $data;
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

    public function getEvents($login)
    {
        if ($this->cache->has('events' . $this->accessToken))
            return json_decode($this->cache->get('events' . $this->accessToken));

        $response = array();
        for ($i = 1; $i <= 3; $i++)
            $response = array_merge($response, json_decode($this->request('https://api.github.com/users/' . $login . '/received_events', 'GET', array('page' => $i))));

        $data = array();
        foreach ($response as $event)
        {
            $event = (object) EventBuilder::build($event);

            if (!isset($data[$event->actor]))
                $data[$event->actor] = array();

            $data[$event->actor][] = $event;
        }

        $this->cache->set('events' . $this->accessToken, json_encode($data));
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

}

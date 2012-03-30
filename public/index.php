<?php

define('GH_CLIENT_ID', '<YOUR CLIENT ID>');
define('GH_CLIENT_SECRET', '<YOUR CLIENT SECRET>');

require_once __DIR__. '/../library/autoload.php';

/**
 * Init
 ******************************************************************************/
$app = new Silex\Application();
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/../src/GitHubSummary/Resources/Views',
));
$app['autoloader']->registerNamespace('GitHubSummary', __DIR__ . '/../src');

/**
 * Filters
 ******************************************************************************/
$app->before(function ($request) use($app) {
    $request->getSession()->start();
    $app['github'] = new GitHubSummary\Services\GitHub($app['session']->get('access_token'));
});

/**
 * Middlewares
 ******************************************************************************/
$mustBeLogged = function ($request) use ($app) {
    if (!$app['session']->has('access_token'))
        return $app->redirect('/login');
};

/**
 * Routes
 ******************************************************************************/
$app->get('/login', function() use($app)
{
    return $app['twig']->render('login.twig', array(
        'url_login' => $app['github']->getAuthorizeUrl(GH_CLIENT_ID)
    ));
})
->bind('login');


$app->get('/login/callback', function() use($app)
{
    $token = $app['github']->getAccessToken(GH_CLIENT_ID, GH_CLIENT_SECRET, $app['request']->get('code'));
    if($token) {
        $app['session']->set('access_token', $token);
        $app['session']->set('user', $app['github']->getUser());
        return $app->redirect('/');
    }

    return $app->redirect('/error');
})
->bind('login_calback');


$app->get('/logout', function() use($app)
{
    $app['session']->clear();
    return $app->redirect('/login');
})
->bind('logout');


$app->get('/', function() use($app)
{
    return $app['twig']->render('index.twig', array(
        'repositories' => $app['github']->getWatchedRepositories(),
        'followingUsers' => $app['github']->getFollowingUsers(),
    ));
})
->bind('home')
->middleware($mustBeLogged);


$app->get('/events', function() use($app)
{
    $user = $app['session']->get('user');
    return $app['twig']->render('events.twig', array(
        'events' => (array) $app['github']->getEvents($user['login'])
    ));
})
->bind('events')
->middleware($mustBeLogged);


$app->run();

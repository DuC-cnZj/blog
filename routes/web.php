<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

/**
 * @link http://patorjk.com/software/taag/#p=display&f=Standard&t=duc's%20blog Text to ASCII
 */
/** @var \Laravel\Lumen\Routing\Router $router */
$router->get('/', function () use ($router) {
    $version = $router->app->version();
    $year = date('Y');

    return <<<TAG
     # welcome! power by {$version} 
      _            _       _     _             
   __| |_   _  ___( )___  | |__ | | ___   __ _ 
  / _` | | | |/ __|// __| | '_ \| |/ _ \ / _` |
 | (_| | |_| | (__  \__ \ | |_) | | (_) | (_| |
  \__,_|\__,_|\___| |___/ |_.__/|_|\___/ \__, |
                                         |___/  created by duc@2018-{$year}.
TAG;
});

$router->get('/nav_links', function () {
    return [
        'data' => [
            ['title' => '首页', 'link' => '/'],
            ['title' => '文章', 'link' => '/articles'],
        ],
    ];
});

$router->get('/login/github', 'AuthController@redirectToProvider');

$router->get('/login/github/callback', 'AuthController@handleProviderCallback');

$router->post('/me', 'AuthController@me');

$router->get('/articles/{id}', 'ArticleController@show');

$router->get('/articles', 'ArticleController@index');

$router->get('/search_articles', 'ArticleController@search');

$router->get('/home_articles', 'ArticleController@home');

$router->get('/newest_articles', 'ArticleController@newest');

$router->get('/popular_articles', 'ArticleController@popular');

$router->get('/trending_articles', 'ArticleController@trending');

$router->get('/top_articles', 'ArticleController@top');

$router->get('/categories', 'CategoryController@index');

$router->get('/articles/{id}/comments', 'CommentController@index');

$router->post('/articles/{id}/comments', 'CommentController@store');

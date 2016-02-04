<?php

//require __DIR__ . '/../autoload.php';
require __DIR__ . '/../vendor/autoload.php';


use Exception\HttpException;
use Http\Request;
use Model\JsonFinder;

// Config
$debug = true;

$app = new \App(new View\TemplateEngine(
    __DIR__ . '/templates/'
), $debug);

/**
 * Index
 */
 // Matches if the HTTP method is GET
$app->get('/', function () use ($app) {
    $finder = new JsonFinder();
    return $app->render('index.php', ['status' => $finder->findAll()]);
});

$app->get('/status', function () use ($app) {
    $finder = new JsonFinder();
    return $app->render('index.php', ['status' => $finder->findAll()]);
});

$app->get('/status/(\d+)', function (Request $request, $id) use ($app) {
    $finder = new JsonFinder();
    if (null === $status = $finder->findOneById($id)) {
        throw new HttpException(404);
    }
    return $app->render('status.php', ['status' => $status]);
});

// Matches if the HTTP method is POST
$app->post('/', function () use ($app) {
    return $app->render('index.php');
});

$app->post('/status', function (Request $request) use ($app) {
    $login = htmlspecialchars($request->getParameter('user'));
    $message = htmlspecialchars($request->getParameter('message'));
    $finder = new JsonFinder();
    $finder->add($login, $message);
    $app->redirect('/status');
});

 // Matches if the HTTP method is PUT
$app->put('/', function () use ($app) {
    return $app->render('index.php');
});

 // Matches if the HTTP method is DELETE
$app->delete('/status/(\d+)', function (Request $request, $id) use ($app) {
    $finder = new JsonFinder();
    if (null == $finder->findOneById($id)) {
        throw new HttpException(404, 'Not Found');
    }
    $finder->delete($id);
    $app->redirect('/status');
});

return $app;

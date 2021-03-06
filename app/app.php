<?php

//require __DIR__ . '/../autoload.php';
require __DIR__.'/../vendor/autoload.php';

use Exception\HttpException;
use Model\Finder\StatusFinder;
use Model\Finder\UserFinder;
use Http\JsonResponse;
use Http\Response;
use Http\Request;
use Model\DataBase\DatabaseConnection;
use Model\DataMapper\StatusMapper;
use Model\DataMapper\UserMapper;
use Model\Entity\Status;
use Model\Entity\User;
use Validation\Validation;

/**
 * Config
 */
$debug = true;
$base = 'uframework';
$login = 'uframework';
$mdp = 'p4ssw0rd';
$host = '127.0.0.1';

/**
 * Connection
 */
$connection = null;
try {
    $connection = new DatabaseConnection('mysql:host='.$host.';dbname='.$base, $login, $mdp);
} catch (PDOException $e) {
    echo 'Connection failed : ' . $e->getMessage();
}

$statusFinder = new StatusFinder($connection);
$userFinder = new UserFinder($connection);
$statusMapper = new StatusMapper($connection);
$userMapper = new UserMapper($connection);

/**
 * Index
 */
$app = new \App(new View\TemplateEngine(
    __DIR__.'/templates/'
), $debug);

 // Matches if the HTTP method is GET -> /
$app->get('/', function () use ($app) {
    $app->redirect('/statuses');
});

// Matches if the HTTP method is GET -> /statuses/
$app->get('/statuses/', function () use ($app) {
    $app->redirect('/statuses');
});

// Matches if the HTTP method is GET -> /login
$app->get('/login', function () use ($app) {
    if (isset($_SESSION['is_connected']) && $_SESSION['is_connected']) {
        throw new HttpException(403, 'Forbidden');
    }

    return $app->render('login.php');
});

// Matches if the HTTP method is GET -> /register
$app->get('/register', function () use ($app) {

    if (isset($_SESSION['is_connected']) && $_SESSION['is_connected']) {
        throw new HttpException(403, 'Forbidden');
    }
    $data['user'] = $data['password'] = $data['confirm'] = '';

    return $app->render('register.php', $data);
});

// Matches if the HTTP method is GET -> /logout
$app->get('/logout', function () use ($app) {
    session_destroy();
    $app->redirect('/statuses');
});

// Matches if the HTTP method is GET -> /statuses
$app->get('/statuses', function (Request $request) use ($app, $statusFinder) {

    if(isset($_SESSION['error'])&& !empty($_SESSION['error'])){
        $data['error'] = $_SESSION['error'];
        $_SESSION['error']="";
    }
    $filter['order'] = $request->getParameter("order") ? htmlspecialchars($request->getParameter("order")) : "";
    $filter['by'] = $request->getParameter("by") ? htmlspecialchars($request->getParameter("by")) : "";
    $filter['limit'] = $request->getParameter("limit") ? htmlspecialchars($request->getParameter("limit")) : "";
    $filter['user_id'] = $request->getParameter("user_id") ? htmlspecialchars($request->getParameter("user_id")) : "";

    ;
    if(null === $data['status'] = $statusFinder->findAll($filter)){
        $data['status']="";
    }

    if ($request->guessBestFormat() === 'json') {
        return new JsonResponse(json_encode($data['status']), 200);
    }
    if (isset($_SESSION['user'])) {
        $data['user'] = $_SESSION['user'];
    } else {
        $data['user'] = 'Unregister User';
    }
    return $app->render('index.php', $data);
});

// Matches if the HTTP method is GET -> /statuses/id
$app->get('/statuses/(\d+)', function (Request $request, $id) use ($app, $statusFinder) {
    if(!Validation::isInt($id)) {
        $response = new Response("Incorrect id parameter",400);
        $response->send();
        return;
    }
    if (null === $data['status'] = $statusFinder->findOneById($id)) {
        if ($request->guessBestFormat() === 'json') {
            return new JsonResponse(json_encode("Status not found"), 204);
        }
        throw new HttpException(404, 'Status not found');
    }
    if ($request->guessBestFormat() === 'json') {
        return new JsonResponse(json_encode($data['status']), 201);
    }
    if (isset($_SESSION['user'])) {
        $data['user'] = $_SESSION['user'];
    } else {
        $data['user'] = 'Unregister User';
    }
    return $app->render('status.php', $data);
});

// Matches if the HTTP method is POST -> /statutes
$app->post('/statuses', function (Request $request) use ($app, $statusFinder, $statusMapper, $userMapper) {
    $data['user'] = htmlspecialchars($request->getParameter('user'));
    $data['message'] = htmlspecialchars($request->getParameter('message'));
    if (empty($data['message'])) {
        $_SESSION['error']="Empty status";
        return $app->redirect('/statuses');
    }
    $status = new Status(null, $data['user'], $data['message'], date('Y-m-d H:i:s'));
    $statusMapper->persist($status);
    if ($request->guessBestFormat() === 'json') {
        return new JsonResponse(json_encode('statuses/'.$status), 201);
    }

   return $app->redirect('/statuses');
});

// Matches if the HTTP method is POST -> /login
$app->post('/login', function (Request $request) use ($app, $userFinder) {
    $data['user'] = $request->getParameter('user');
    $data['password'] = $request->getParameter('password');
    if (Validation::validateConnection($data['user'], $data['password'])) {
        $data['error'] = 'Empty Username or password';
        return $app->render('login.php', $data);
    }
    if (null == $user = $userFinder->findOneByUserName($data['user'])) {
        $data['error'] = 'Unknown user';
        return $app->render('login.php', $data);
    }
    if (!password_verify($data['password'], $user->getUserPassword())) {
        $data['error'] = 'Bad password';
        return $app->render('login.php', $data);
    }
    $_SESSION['id'] = $user->getUserId();
    $_SESSION['user'] = $user->getUserName();
    $_SESSION['is_connected'] = true;

    return $app->redirect('/statuses');
});

// Matches if the HTTP method is POST -> /register
$app->post('/register', function (Request $request) use ($app, $userMapper) {
    $data['user'] = $request->getParameter('user');
    $data['password'] = $request->getParameter('password');
    $data['confirm'] = $request->getParameter('confirm');
    $data['captcha'] = $request->getParameter('captcha');
    $data['error'] = Validation::validationRegisterForm($data['user'], $data['password'], $data['confirm'], $data['captcha']);
    if ($data['error']['nb'] > 0) {
        return $app->render('register.php', $data);
    }
    $userMapper->persist(new User(null, $data['user'], password_hash($data['password'], PASSWORD_DEFAULT)));

    return $app->redirect('/login');
});

// Matches if the HTTP method is PUT -> /
$app->put('/', function () use ($app) {
    return $app->render('index.php');
});

// Matches if the HTTP method is DELETE -> /statuses/id
$app->delete('/statuses/(\d+)', function (Request $request, $id) use ($app, $statusFinder, $statusMapper) {
    if(!Validation::isInt($id)) {
        $response = new Response("Incorrect id parameter",400);
        $response->send();
        return;
    }
    if (null == $statusFinder->findOneById($id)) {
        throw new HttpException(404, 'Status not Found');
    }
    $statusMapper->remove($id);
    return $app->redirect('/statuses');
});

// Firewall
$app->addListener('process.before', function (Request $req) use ($app) {

    session_start();

    $allowed = [
        '/login' => [Request::GET, Request::POST],
        '/statuses' => [Request::GET, Request::POST],
        '/statuses/' => [Request::GET, Request::POST],
        '/register' => [Request::GET, Request::POST],
        '/' => [Request::GET],
    ];
    if (isset($_SESSION['is_connected'])
        && true === $_SESSION['is_connected']) {
        return;
    }
    foreach ($allowed as $pattern => $methods) {
        if (preg_match(sprintf('#^%s$#', $pattern), $req->getUri())
            && in_array($req->getMethod(), $methods)) {
            return;
        }
    }

    switch ($req->guessBestFormat()) {
        case 'json':
            throw new HttpException(401);
    }

    return $app->redirect('/login');
});

return $app;

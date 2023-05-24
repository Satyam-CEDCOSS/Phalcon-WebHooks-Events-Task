<?php

require_once  __DIR__ . "/vendor/autoload.php";

use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\Di\FactoryDefault;
use Phalcon\Events\Manager;
use Phalcon\Acl\Adapter\Memory;
use Phalcon\Security\JWT\Builder;
use Phalcon\Security\JWT\Signer\Hmac;
use Phalcon\Security\JWT\Token\Parser;

$loader = new Loader();
$loader->registerNamespaces(
    [
        'MyApp\Models' => __DIR__ . '/models/',
        'MyApp\Handlers' => __DIR__ . '/handlers/',
    ]
);



$loader->register();

$container = new FactoryDefault();


$manager = new Manager();
$manager->attach(
    'micro:beforeExecuteRoute',
    function () {
        $role = $_GET['role'];
        $signer  = new Hmac();
        $builder = new Builder($signer);
        $passphrase = 'QcMpZ&b&mo3TPsPk668J6QH8JA$&U&m2';
        $builder
            ->setSubject($role)
            ->setPassphrase($passphrase);
        $token = $builder->getToken();
        $parser = new Parser();
        $tokenObject = $parser->parse($token->getToken());
        $role = $tokenObject->getclaims()->getpayload()['sub'];

        $acl = new Memory();
        $acl->addRole('user');
        $acl->addRole('admin');
        $new = $_GET['_url'];

        $ar = explode("/", $new);
        $acl->addComponent(
            'product',
            [
                'search',
                'get',

            ]
        );
        $acl->addComponent(
            'order',
            [
                'create',
                'update',

            ]
        );
        $acl->allow("admin", '*', '*');
        $acl->allow("user", 'product', 'search');
        $acl->allow("user", 'order', 'create');
        if (!($acl->isAllowed($role, $ar[1], $ar[2]))) {
            echo '<h1>Access denied :(</h1>';
            die;
        }
    }

);

$container->set(
    'mongo',
    function () {
        $mongo = new MongoDB\Client(
            "mongodb+srv://root:Password123@mycluster.qjf75n3.mongodb.net/?retryWrites=true&w=majority"
        );

        return $mongo->webhooksdb;
    },
    true
);

$app = new Micro($container);
$app->setEventsManager($manager);

$app->get(
    '/product/search/{keyword}',
    function ($keyword) {
        $movies = $this->mongo->data->find();
        $pieces = array();
        $pieces = explode("%20", $keyword);
        foreach ($movies as $movie) {
            foreach ($pieces as $value) {
                $pattern = "/$value/i";
                if (preg_match_all($pattern, $movie->name)) {
                    $data[] = [
                        'id'   => $movie->_id,
                        'name' => $movie->name,
                        'type' => $movie->type,
                        'quantity' => $movie->quantity,
                    ];
                }
            }
        }
        echo json_encode($data);
    }
);

$app->get(
    '/product/get',
    function () {
        $movies = $this->mongo->data->find();

        $data = [];

        if ($_GET['per_page']) {
            $per = $_GET['per_page'];
            echo $per;
            if ($_GET['page']) {
                $page = $_GET['page'];
                echo $page;
            } else {
                $page = 0;
                echo $page;
            }
            foreach ($movies as $movie) {
                $data[] = [
                    'id'   => $movie->_id,
                    'name' => $movie->name,
                    'type' => $movie->type,
                    'quantity' => $movie->quantity,
                ];
            }
            $data = array_slice($data, $per * $page, $per);
        } else {
            foreach ($movies as $movie) {
                $data[] = [
                    'id'   => $movie->_id,
                    'name' => $movie->name,
                    'type' => $movie->type,
                    'quantity' => $movie->quantity,
                ];
            }
        }


        echo json_encode($data);
    }
);

$app->post(
    '/product/create',
    function () {
        $success = $this->mongo->data->insertOne($_POST);
        $data = $this->mongo->webhooks->find();
        foreach ($data as $value) {
            if ($value->event == 'create') {
                $call = new MyApp\Handlers\Modify();
                $call->create($_POST);
                break;
            }
        }
        echo $success->getInsertedCount();
    }
);

$app->put(
    '/product/update',
    function () use ($app) {
         
        $update = $app->request->getJsonRawBody();
        $id = $update->id;
        $quantity = $update->quantity;
        $this->mongo->data->updateOne(array("_id" =>
        new MongoDB\BSON\ObjectId($id)), array('$set' => ['quantity' => $quantity]));
        $data = $this->mongo->webhooks->find();
        foreach ($data as $value) {
            if ($value->event == 'update') {
                $info = $this->mongo->data->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
                $arr = [
                    'name'=>$info->name,
                    'quantity'=>$quantity
                ];
                $call = new MyApp\Handlers\Modify();
                $call->update($arr);
                break;
            }
        }
    }
);

$app->handle(
    $_SERVER["REQUEST_URI"]
);

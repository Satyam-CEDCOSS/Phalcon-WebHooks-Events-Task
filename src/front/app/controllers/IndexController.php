<?php

use Phalcon\Mvc\Controller;

session_start();

class IndexController extends Controller
{
    public function indexAction()
    {
        // Redirect to View
    }
    public function checkAction()
    {
        $check = $this->mongo->users->findOne(['$and' => [['email' => $_POST['email']],
         ['password' => $_POST['password']]]]);
        $_SESSION['type'] = $check->type;
        if ($check['_id']) {
            $this->response->redirect('/product');
        }
    }
}

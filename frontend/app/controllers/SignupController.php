<?php

use Phalcon\Mvc\Controller;


class SignupController extends Controller
{
    public function indexAction()
    {
        // Redirect to View
    }
    public function addAction()
    {
        $file = $this->mongo->users;
        $arr = [
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'password' => $_POST['password'],
            'type' => 'user',
        ];
        $file->insertOne($arr);
        $this->response->redirect('/');
    }
}

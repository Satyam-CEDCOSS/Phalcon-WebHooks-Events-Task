<?php

use Phalcon\Mvc\Controller;

session_start();

class WebhookController extends Controller
{
    public function indexAction()
    {
        // redirect to view
    }
    public function addAction()
    {
        $result = $this->mongo->webhooks->insertOne($_POST);
        if ($result->getInsertedCount()) {
            $this->response->redirect('/webhook/done');
        }
    }
    public function doneAction()
    {
        // redirect to view
    }
    public function displayAction()
    {

        $data = $this->mongo->webhooks->find();
        $display = "";
        foreach ($data as $value) {
            $display .= '<tr>
                <td>' . $value->name . '</td><td>' . $value->event . '</td>
                <td>' . $value->key . '</td>
                <td><a href="/webhook/delete?id='.$value->_id.'" class="btn btn-danger">Delete</a></td>
                </tr>';
        }
        $this->view->display  = $display;
    }
    public function deleteAction()
    {
        $id = $_GET['id'];
        $this->mongo->webhooks->deleteOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $this->response->redirect('/webhook/display');
    }
}

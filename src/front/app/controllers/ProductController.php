<?php

use Phalcon\Mvc\Controller;

session_start();

class ProductController extends Controller
{
    public function indexAction()
    {
        $data = $this->mongo->data->find();
        $txt = '<option selected disabled>-Select-</option>';
        foreach ($data as $value) {
            $txt .= '<option value=' . $value->name . '>' . $value->name . '</option>';
        }
        $this->view->result = $txt;
    }
    public function addAction()
    {
        $url = "http://192.168.16.2/product/create?role=admin";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        if ($result) {
            $this->response->redirect('/product/done');
        }
    }
    public function doneAction()
    {
        // redirect to view
    }
    public function displayAction()
    {
        if ($_SESSION['type'] != 'admin') {
            echo "<h2>Access Denied :(</h2>";
            die;
        } else {
            $data = $this->mongo->data->find();
            $display = "";
            foreach ($data as $value) {
                $display .= '<tr>
                <td>' . $value->name . '</td><td>' . $value->company . '</td>
                <td>' . $value->type . '</td><td>' . $value->qunatity . '</td>
                </tr>';
            }
            $this->view->display  = $display;
        }
    }
}

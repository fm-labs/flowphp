<?php
namespace Flow\TestApp\Controller;

use Flow\App\Controller;

class HelloWorldController extends Controller
{

    public function indexAction()
    {
        echo 'Hello World';
    }

    public function jsonAction()
    {
        $this->response()->setContentType('text/plain');

        echo json_encode(['hello' => 'world']);
    }
}
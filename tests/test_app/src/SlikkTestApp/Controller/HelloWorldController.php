<?php
namespace FlowTestApp\Controller;

use Flow\Core\Controller;

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
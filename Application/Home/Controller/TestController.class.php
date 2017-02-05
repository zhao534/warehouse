<?php
namespace Home\Controller;
use Think\Controller;
class TestController extends \Home\Controller\BaseController {
    public function index(){
    	$data = array('a','b','c');
    	dump(array_search('d',$data));
    }
}
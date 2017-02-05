<?php
namespace Home\Controller;
use Think\Controller;
class GoodsController extends \Home\Controller\BaseController {
    public function index(){
    	$page_number = C('PAGE_NUMBER');
    	
    	$model_goods = D('Goods');
    	$array = $model_goods->show_page($page_number);
    	$this->assign('str',$array["str"]);// 赋值数据集
    	$this->assign('data',$array["data"]);// 赋值分页输出
   		
    	$this->getData();
    	
    	$this->display();
    }
	
    //添加
	public function add(){
		if(IS_POST){
			//创建模型
			$model_goods = D('Goods');
			if($model_goods->create()){
				if($model_goods->add()){
					$this->success("添加成功！",U("index"));
					exit;
				}
				else{
					if(APP_DEBUG){
						$sql = $model_goods->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			//表单验证失败
			else{
				$error = $model_goods->getError();
				$this->error(implode("<br/>",$error));
			}
		}
		
		$this->getData();
		//显示表单
		$this->display();
	}
	
	
	//编辑
	public function edit($id){
		$model_goods = D('Goods');		
		$data = $model_goods->find($id);
		$this->assign("data",$data);
		
		$this->getData();
		
		$this->display();
	}
	
	//index,add,edit公用的取规格表，分类表，库房表数据语句
	public function getData(){
		//商品规格表
		$model_type = D('type');
		$this->assign("data_type",$model_type->select());
		//商品分类表
		$model_category = D('category');
		$this->assign("data_category",$model_category->getTree());
		//商品库房表
		$model_stockhouse = D('stockhouse');
		$this->assign("data_stockhouse",$model_stockhouse->select());
	}
	
	public function update(){
		if(IS_POST){
			$model_goods = D('Goods');
			if($model_goods->create()){
				$result = $model_goods->save();
				if($result!==FALSE)
					$this->success("修改数据成功！",U('index'));
				else 
				{
					if(APP_DEBUG){
						$sql = $model_goods->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			else{
				//返回一个数组，拼装成字符串
				$error = $model_goods->getError();
				$this->error(implode("<br/>",$error));
			}
		}
	}
	
	//删除用户 
	public function del($id){
		$model_goods = D('Goods');
		$result = $model_goods->delete($id);
		if($result)
			$this->success("删除成功！",U('index'));
		else 
			$this->error("删除失败,请刷新重试！");
	}
	
	//批量删除用户
	public function bdel(){
		$did = I('post.delid');
		$str = implode(',', $did);
		
		//调用删除方法
		$this->del($str);
	}
	
	//ajax
	public function getGoodsByTypeId($type_id){
		$model_goods = D('goods');
		$data = $model_goods->field('id,name')->where(array('type_id'=>$type_id))->select();
		$this->ajaxReturn($data,"JSON");
	
		
	}
	
	//ajax获取选中分类下对应的商品
	public function getGoodsByCatId($cat_id){
		$model_goods = D('goods');
		$data = $model_goods->field('id,name')->where(array('cat_id'=>$cat_id))->select();
		$this->ajaxReturn($data,"JSON");
	}
}
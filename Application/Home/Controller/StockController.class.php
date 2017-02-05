<?php
namespace Home\Controller;
use Think\Controller;
class StockController extends \Home\Controller\BaseController {
    public function index(){
    	$page_number = C('PAGE_NUMBER');
    	
    	$model_stock  = D('Stock');
    	
    	$array = $model_stock->show_page($page_number);
    	$this->assign('str',$array["str"]);// 赋值数据集
    	$this->assign('data',$array["data"]);// 赋值分页输出
    	
    	//dump($array['data']);
    	
    	R('Goods/getData');
    	
    	$this->display();
    }
	
    //添加
	public function add(){
		if(IS_POST){
// 			dump(I("post."));
			//创建模型
			$model_stock = D('Stock');
			if($model_stock->create()){
				if($model_stock->add()){
					$this->success("添加成功！",U("index"));
					exit;
				}
				else{
					if(APP_DEBUG){
						$sql = $model_stock->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			//表单验证失败
			else{
				$error = $model_stock->getError();
				$this->error(implode("<br/>",$error));
			}
		}
		
		//商品分类表
		$model_type = D('type');
		$this->assign("data_type",$model_type->select());
		//显示表单
		$this->display();
	}
	
	
	//编辑用户
	public function edit($id){
		
		$model_stock = D('stock');
		
		$data = $model_stock->getDataByEdit($id);
		
		$this->assign("data",$data[0]);
		
		//dump($data);
		
		//商品分类表
		$model_category = D('category');
		$this->assign("data_category",$model_category->getTree());
		
		$this->display();
	}
	
	public function update(){
		if(IS_POST){
			$model_stock = D('Stock');
			if($model_stock->create()){
				$result = $model_stock->save();
				if($result!==FALSE)
					$this->success("修改数据成功！",U('index'));
				else 
				{
					if(APP_DEBUG){
						$sql = $model_stock->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			else{
				//返回一个数组，拼装成字符串
				$error = $model_stock->getError();
				$this->error(implode("<br/>",$error));
			}
		}
	}
	
	//删除用户 
	public function del($id){
		$model_stock = D('Stock');
		$result = $model_stock->delete($id);
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
	
	//获取库存数量
	public function getStock(){
		$data = I('post.');
		
		$goods_name = $data['goods_name'];
		$type_name  = $data['type_name'];
		$stock_id   = $data['stock_id'];
		
		$model_stock = D('stock');
		
		$result = array();
		
		foreach ($goods_name as $k=>$v){
			$data = $model_stock
			->alias('a')
			->field('a.quantity_stock')
			->join('wh_goodsname b ON b.id=a.id_goodsname')
			->join('wh_typename c ON c.id=a.id_typename')
			->where("a.stock_id={$stock_id} AND b.name='{$goods_name[$k]}' AND c.name='{$type_name[$k]}'")
			->find();
			
			if($data)
				$result[] = $data['quantity_stock'];
			else
				$result[] = 0;
		}
		
		$this->ajaxReturn($result,"JSON");
	}
}
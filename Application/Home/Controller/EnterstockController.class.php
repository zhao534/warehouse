<?php
namespace Home\Controller;
use Think\Controller;
class EnterstockController extends \Home\Controller\BaseController {
    public function index(){
    	$page_number = C('PAGE_NUMBER');
    	
    	$model_enterstock = D('Enterstock');
    	$array = $model_enterstock->show_page($page_number);
    	$this->assign('str',$array["str"]);// 赋值数据集
    	$this->assign('data',$array["data"]);// 赋值分页输出
    	
    	R('Goods/getData');
    	
//     	dump($array["data"]);
   			
    	$this->display();
    }
	
    //添加
	public function add(){
		if(IS_POST){
//  		dump(I('post.'));
//    			die;
			//创建模型
			$model_enterstock = D('Enterstock');
			
			if($model_enterstock->create()){
				if($model_enterstock->add()){
					$this->success("添加成功！",U("index"));
					exit;
				}
				else{
					if(APP_DEBUG){
						$sql = $model_enterstock->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			//表单验证失败
			else{
				$error = $model_enterstock->getError();
				$this->error(implode("<br/>",$error));
			}
		}
		
		//R方法
		R("Goods/getData");
		
		$model_user = D('user');
		$this->assign("data_user",$model_user->field('id,username')->select());
		//显示表单
		$this->display();
	}
	
	
	//编辑用户
	public function edit($id){
		$model_enterstock 		 = D('Enterstock');
		$model_enterstockdetails = D('Enterstockdetails');
		$model_user  			 = D('User');
		$model_stockhouse 		 = D('Stockhouse');
		$model_type  			 = D('Typename');
		
		$data = $model_enterstock->find($id);
		$data_enterstockdetails = $model_enterstockdetails->alias("a")
		->field("
				a.*,
				b.name AS name_goods,
				c.id AS id_type,c.name AS name_type,
				d.stock_id,
				e.name AS name_stock")
		->join('wh_goodsname b ON a.id_goodsname=b.id')
		->join('wh_typename c ON a.id_typename=c.id')
		->join('wh_enterstock d ON a.id_enter=d.id')
		->join('wh_stockhouse e ON d.stock_id=e.id')
		->where("a.id_enter={$id}")
		->order('a.id asc')
		->select();
		

// 		var_dump($model_enterstockdetails->getLastSql());
// 		dump($data_enterstockdetails);	
			
		$this->assign("data",$data);
		$this->assign("data_type",$model_type->select());
		$this->assign("data_user",$model_user->field('id,username')->select());
		$this->assign("data_stockhouse",$model_stockhouse->field('id,name')->select());
		$this->assign("data_enterstockdetails",$data_enterstockdetails);
		
		$this->display();
	}
	
	public function update(){
		if(IS_POST){
// 			dump(I('post.'));
// 			die;
			$model_enterstock = D('Enterstock');
			if($model_enterstock->create()){
				$result = $model_enterstock->save();
				if($result!==FALSE)
					$this->success("修改数据成功！",U('index'));
				else 
				{
					if(APP_DEBUG){
						$sql = $model_enterstock->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			else{
				//返回一个数组，拼装成字符串
				$error = $model_enterstock->getError();
				$this->error(implode("<br/>",$error));
			}
		}
	}
	
	//删除
	public function del($id){
		//删除入库表记录后，将对应的入库详细表记录删除
		$model_enterstock = D('Enterstock');
		$model_enterstockdetails = D('Enterstockdetails');
		$model_stock = M('stock');
		
		//找到对应的入库详情单+库存信息
		
		$details = $model_enterstockdetails
		->alias('a')
		->field('a.*,b.stock_id,c.quantity_stock')
		->where("a.id_enter IN ({$id})")
		->join('wh_enterstock b ON b.id=a.id_enter')
		->join("wh_stock c ON c.id_goodsname=a.id_goodsname AND c.id_typename=a.id_typename AND c.stock_id=b.stock_id")
		->select();
		
// 		var_dump($model_enterstockdetails->getLastSql());
// 		dump($details);
// 		die;
		
		if(!$details){
			//可能是在入库详情管理中已经删除了记录
			//所以只需删除入库单信息即可
			$result = $model_enterstock->delete($id);
			if($result)
				$this->success("删除成功！",U('index'));
			else 
				$this->error("删除失败,请刷新重试！");
			exit;
		}
		
		//修改库存，再删除入库详细表记录
		foreach($details as $k=>$v){
			//库存
			$quantity = $v['quantity_stock']-$v['quantity_enter'];
			
			$data = array(
					'quantity_stock'=>$quantity,
					'time_update'	=>date("Y-m-d H:i:s",time())
			);
			
			$model_stock->where("id_goodsname={$v['id_goodsname']} AND id_typename={$v['id_typename']} AND stock_id={$v['stock_id']}")->setField($data);
			//入库详情单
			$model_enterstockdetails->delete($v['id']);
		}
		
		//删除入库单记录
		$result = $model_enterstock->delete($id);
		if($result)
			$this->success("删除成功！",U('index'));
		else 
			$this->error("删除失败,请刷新重试！");
	}
	
	//批量删除
	public function bdel(){
		$did = I('post.delid');
		$str = implode(',', $did);
		
		//调用删除方法
		$this->del($str);
	}
	
	//显示明细单方法
	public function lst($id){
		$model_enterstock = D('Enterstock');
		$model_enterstockdetails = D('Enterstockdetails');
		
		$data_total = $model_enterstock
		->alias('a')
		->field('a.*,b.username,c.name as name_stock')
		->join("wh_user b ON b.id=a.user_id")
		->join("wh_stockhouse c ON c.id=a.stock_id")
		->where("a.id={$id}")->find();
		
		$data_details = $model_enterstockdetails
		->alias('a')
		->field('a.*,b.name AS name_goods,d.name AS name_type')
		->join('wh_enterstock e ON e.id=a.id_enter')
		->join("wh_goodsname b ON b.id=a.id_goodsname")
		->join("wh_typename d ON d.id=a.id_typename")
		->where("a.id_enter={$id}")
		->select();
		
		//dump($data_total);
// 		dump($data_details);
		
		$this->assign("data_total",$data_total);
		$this->assign("data_details",$data_details);
		$this->display();
	}
}
<?php
namespace Home\Controller;
use Think\Controller;
class LeavestockController extends \Home\Controller\BaseController {
    public function index(){
    	$page_number = C('PAGE_NUMBER');
    	
    	$model_leavestock = D('Leavestock');
    	$array = $model_leavestock->show_page($page_number);
    	$this->assign('str',$array["str"]);// 赋值数据集
    	$this->assign('data',$array["data"]);// 赋值分页输出
    	
//     	dump($array["data"]);
    	
    	R('Goods/getData');
   			
    	$this->display();
    }
	
    //添加
	public function add(){
		if(IS_POST){
			
			//创建模型
			$model_leavestock = D('Leavestock');
			
			if($model_leavestock->create()){
				if($model_leavestock->add()){
					$this->success("添加成功！",U("index"));
					exit;
				}
				else{
					if(APP_DEBUG){
						$sql = $model_leavestock->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			//表单验证失败
			else{
				$error = $model_leavestock->getError();
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
	
	
	//编辑
	public function edit($id){
		$model_leavestock 		 = D('Leavestock');
		$model_leavestockdetails = D('Leavestockdetails');
		$model_user  			 = D('User');
		$model_stockhouse 		 = D('Stockhouse');
		
		$data = $model_leavestock
		->alias("a")
		->field('a.*,b.name as name_project')
		->join('wh_project b ON b.id=a.project_id')
		->where("a.id={$id}")
		->find();

		$data_leavestockdetails = $model_leavestockdetails
		->alias("a")
		->field("a.*,b.name AS name_goods,c.name AS name_type")
		->join('wh_goodsname b ON a.id_goodsname=b.id')
		->join('wh_typename c  ON a.id_typename=c.id')
		->where("a.id_leave={$id}")
		->select();
					
		$this->assign("data",$data);
		$this->assign("data_user",$model_user->field('id,username')->select());
		$this->assign("data_stockhouse",$model_stockhouse->field('id,name')->select());
		$this->assign("data_leavestockdetails",$data_leavestockdetails);
		
// 		dump($data);
// 		dump($data_leavestockdetails);
		
		$this->display();
	}
	
	public function update(){
		if(IS_POST){
			$model_leavestock = D('leavestock');
			if($model_leavestock->create()){
				$result = $model_leavestock->save();
				if($result!==FALSE)
					$this->success("修改数据成功！",U('index'));
				else 
				{
					if(APP_DEBUG){
						$sql = $model_leavestock->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			else{
				//返回一个数组，拼装成字符串
				$error = $model_leavestock->getError();
				$this->error(implode("<br/>",$error));
			}
		}
	}
	
	//删除
	public function del($id){
		//删除出库表记录后，将对应的出库详细表记录删除
		$model_leavestock = M('leavestock');
		$model_leavestockdetails = M('leavestockdetails');
		$model_stock = M('stock');
		
		//找到对应的出库详情单+库存信息
		$details = $model_leavestockdetails
		->alias('a')
		->field('a.*,b.quantity_stock,c.stock_id')
		->where("a.id_leave IN ({$id})")
		->join('wh_leavestock c ON c.id=a.id_leave')
		->join("wh_stock b ON b.id_goodsname=a.id_goodsname AND b.id_typename=a.id_typename AND c.stock_id=b.stock_id")
		->select();
		
// 		var_dump($model_leavestockdetails->getLastSql());
// 		dump($details);
// 		die;
		
		if(!$details){
			//可能是在出库详情管理中已经删除了记录
			//所以只需删除出库单信息即可
			$result = $model_leavestock->delete($id);
			if($result)
				$this->success("删除成功！",U('index'));
			else 
				$this->error("删除失败,请刷新重试！");
			exit;
		}
		
		//修改库存，再删除出库详细表记录
		foreach($details as $k=>$v){
			
			$data = array(
					'quantity_stock' => $v['quantity_stock']+$v['quantity_leave'],
					'time_update'	 => date("Y-m-d H:i:s",time())
			);
			
			$model_stock
			->where("id_goodsname={$v['id_goodsname']} AND id_typename={$v['id_typename']} AND stock_id={$v['stock_id']}")
			->setField($data);
			
			//出库详情单
			$model_leavestockdetails->delete($v['id']);
		}
		
		//删除出库单记录
		$result = $model_leavestock->delete($id);
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
		$model_leavestock = D('leavestock');
		$model_leavestockdetails = D('leavestockdetails');
		
		$data_total = $model_leavestock
		->alias('a')
		->field('a.*,b.username,c.name as name_project,d.name AS name_stock')
		->join("wh_user b ON b.id=a.user_id")
		->join('wh_project c ON c.id=a.project_id')
		->join("wh_stockhouse d ON d.id=a.stock_id")
		->where("a.id={$id}")
		->find();
		
		$data_details = $model_leavestockdetails
		->alias('a')
		->field('a.*,b.name AS name_goods,d.name AS name_type,f.price_enter')
		->join('wh_leavestock e ON e.id=a.id_leave')
		->join("wh_goodsname b ON b.id=a.id_goodsname")
		->join("wh_typename d ON d.id=a.id_typename")
		->join("wh_stock f ON f.id_typename=a.id_typename AND f.id_goodsname=a.id_goodsname AND f.stock_id=e.stock_id")
		->where("a.id_leave={$id}")
		->select();
		
// 		dump($data_details);
		
		$this->assign("data_total",$data_total);
		$this->assign("data_details",$data_details);
		$this->display();
	}
}
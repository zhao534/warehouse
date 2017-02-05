<?php
namespace Home\Controller;
use Think\Controller;
class LeavestockdetailsController extends \Home\Controller\BaseController {
    public function index(){
    	$page_number = C('PAGE_NUMBER');
    	
    	$model_leavestockdetails = D('leavestockdetails');
    	$array = $model_leavestockdetails->show_page($page_number);
    	$this->assign('str',$array["str"]);// 赋值数据集
    	$this->assign('data',$array["data"]);// 赋值分页输出
   		
    	R('Goods/getData');
    	
    	$this->display();
    }
    
    //此方法即为index的不带分页实现
    public function printresult(){
    	$get = I('get.');
    	
    	//查询条件
    	$map = 1;
    	 
    	//搜索条件：
    	//订单号
    	$order = I('get.order');
    	//商品库房
    	$stock = I('get.stock');
    	//时间段
    	$end = I('get.end');
    	$start = I('get.start');
    	//数量
    	$min = intval(I('get.min'));
    	$max = intval(I('get.max'));
    	//型号
    	$type = I('get.type');
    	//商品名称
    	$name = I('get.name');
    	//工程名称
    	$project = I('get.project');
    	
    	
    	//订单号
    	if($order)
    		$map .= " AND a.id_leave={$order}";

    	//库房
    	if($stock)
    		$map .= " AND f.id={$stock}";
    	 
    	//时间段
    	if($end){
    		$start = I('get.start');
    	
    		if(empty($start))
    			$map .= " AND UNIX_TIMESTAMP('{$end}')>=UNIX_TIMESTAMP(b.leavedate)";
    		else
    			$map .= " AND UNIX_TIMESTAMP('{$start}')<=UNIX_TIMESTAMP(b.leavedate) AND UNIX_TIMESTAMP('{$end}')>=UNIX_TIMESTAMP(b.leavedate)";
    	}
    	 
    	//数量
    	if($min>0){
    		if($max==0)
    			$map .= " AND a.quantity_leave>={$min}";
    		else if($min<$max)
    			$map .= " AND a.quantity_leave>={$min} AND a.quantity_leave<={$max}";
    	}
    	else if($max>0)
    		$map .= " AND a.quantity_leave<={$max}";
    	 
    	//型号
    	if($type)
    		$map .= " AND e.name LIKE '%{$type}%'";
    	 
    	//名称
    	if($name)
    		$map .= " AND d.name LIKE '%{$name}%'";
    	 
    	//工程名称
    	if($project){
    		$map .= " AND c.name LIKE '%{$project}%'";
    	}
    	
    	$model_leavestockdetails = M('leavestockdetails');
    	
    	//数据查询
    	$data =$model_leavestockdetails->alias('a')
    	->field('
			a.*,
			b.leavedate,
			c.name as name_project,
			d.name as name_goods,
			e.name as name_type,
			f.name as name_stockhouse,
			g.price_enter
		')
    	->join('wh_leavestock b ON a.id_leave=b.id')
    	->join('wh_project c ON c.id=b.project_id')
        ->join('wh_goodsname d ON a.id_goodsname=d.id')
    	->join('wh_typename e ON a.id_typename=e.id')
    	->join('wh_stockhouse f ON b.stock_id=f.id AND FIND_IN_SET('.session('userid').',f.user_id_list)')
    	->join('wh_stock g ON g.id_goodsname=a.id_goodsname AND g.id_typename=a.id_typename AND g.stock_id=b.stock_id')
    	//->join('wh_user h ON b.user_id=h.id')
    	//排列根据出库单据号和出库时间
    	->order("a.id_leave desc,b.leavedate asc")
    	->where($map)
    	->select();
    	 
    	
    	$this->assign("data",$data);

    	$this->display();
    }
	
    //添加
	public function add(){
		if(IS_POST){
// 			dump(I('post.'));
// 			die;
			//创建模型
			$model_leavestockdetails = D('leavestockdetails');
			if($model_leavestockdetails->create()){
				if($model_leavestockdetails->add()){
					$this->success("添加成功！",U("index"));
					exit;
				}
				else{
					if(APP_DEBUG){
						$sql = $model_leavestockdetails->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			//表单验证失败
			else{
				$error = $model_leavestockdetails->getError();
				$this->error(implode("<br/>",$error));
			}
		}
		
		//商品分类表
		$model_type = D('type');
		$this->assign("data_type",$model_type->select());
		
		//商品库房表
		$model_stock = D('stockhouse');
		$this->assign("data_stock",$model_stock->select());
			
		//显示表单
		$this->display();
	}
	
	
	//编辑用户
	public function edit($id){
		$model_leavestockdetails = D('leavestockdetails');
		
		$data = $model_leavestockdetails
		->alias('a')
		->field('a.*,b.name AS name_goods,c.name AS name_type')
		->join("wh_goodsname b ON b.id=a.id_goodsname")
		->join("wh_typename c ON c.id=a.id_typename")
		->where("a.id={$id}")
		->find();
		
		//dump($data);
		
		$this->assign("data",$data);
		
		//商品分类表
		//$model_type = D('type');
		//$this->assign("data_type",$model_type->select());
		
		//商品库房表
		//$model_stock = D('stockhouse');
		//$this->assign("data_stock",$model_stock->select());
		
		$this->display();
	}
	
	public function update(){
		if(IS_POST){
			$model_leavestockdetails = D('leavestockdetails');
			if($model_leavestockdetails->create()){
				$result = $model_leavestockdetails->save();
				if($result!==FALSE)
					$this->success("修改数据成功！",U('index'));
				else 
				{
					if(APP_DEBUG){
						$sql = $model_leavestockdetails->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			else{
				//返回一个数组，拼装成字符串
				$error = $model_leavestockdetails->getError();
				$this->error(implode("<br/>",$error));
			}
		}
	}
	
	//删除
	public function del($id){
		$model_leavestockdetails = D('leavestockdetails');
		$model_stock = M('stock');
		
		$details = $model_leavestockdetails
		->alias('a')
		->field('a.*,b.quantity_stock,b.id bid')
		->join('wh_leavestock c ON c.id=a.id_leave')
		->join("wh_stock b ON b.id_goodsname=a.id_goodsname AND b.id_typename=a.id_typename AND b.stock_id=c.stock_id")
		->where("a.id IN ($id)")
		->select();
		
// 		if(APP_DEBUG)
// 			dump($details);
// 		var_dump($model_leavestockdetails->getLastSql());
		
		//修改库存
		foreach($details as $k=>$v){
			//把数量加回去
			$quantity = $v['quantity_stock']+$v['quantity_leave'];
			
			$data = array(
					'quantity_stock'=>$quantity,
					'time_update'	=>date("Y-m-d H:i:s",time())
			);
			
			$model_stock
			->where("id={$v['bid']}")
			->setField($data);	
		}
		
		//删除入库详情表记录
		$result = $model_leavestockdetails->delete($id);
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
}
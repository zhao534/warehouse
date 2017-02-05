<?php
namespace Home\Controller;
use Think\Controller;
class EnterstockdetailsController extends \Home\Controller\BaseController {
    public function index(){
    	$page_number = C('PAGE_NUMBER');
    	
    	$model_enterstockdetails = D('Enterstockdetails');
    	$array = $model_enterstockdetails->show_page($page_number);
    	$this->assign('str',$array["str"]);// 赋值数据集
    	$this->assign('data',$array["data"]);// 赋值分页输出
   		
    	R('Goods/getData');
    	
    	$this->display();
    }
    
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
    		$map .= " AND a.id_enter={$order}";
    
    	//库房
    	if($stock)
    		$map .= " AND f.id={$stock}";

    
    	//时间段
    	if($end){
    		$start = I('get.start');
    		 
    		if(empty($start))
    			$map .= " AND UNIX_TIMESTAMP('{$end}')>=UNIX_TIMESTAMP(b.enterdate)";
    		else
    			$map .= " AND UNIX_TIMESTAMP('{$start}')<=UNIX_TIMESTAMP(b.enterdate) AND UNIX_TIMESTAMP('{$end}')>=UNIX_TIMESTAMP(b.enterdate)";
    	}
    
    	//数量
    	if($min>0){
    		if($max==0)
    			$map .= " AND a.quantity_enter>={$min}";
    		else if($min<$max)
    			$map .= " AND a.quantity_enter>={$min} AND a.quantity_enter<={$max}";
    	}
    	else if($max>0)
    		$map .= " AND a.quantity_enter<={$max}";
    
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
 
    	$model_enterstockdetails = M('enterstockdetails');
    	 
    	//数据查询
    	$data =$model_enterstockdetails->alias('a')
    	->field('
			a.*,
			b.enterdate,
			d.name as name_goods,
			e.name as name_type,
			f.name as name_stockhouse,
			g.price_enter
		')
    		->join('wh_enterstock b ON a.id_enter=b.id')
    		->join('wh_goodsname d ON a.id_goodsname=d.id')
    		->join('wh_typename e ON a.id_typename=e.id')
    		->join('wh_stockhouse f ON b.stock_id=f.id AND FIND_IN_SET('.session('userid').',f.user_id_list)')
    		->join('wh_stock g ON g.id_goodsname=a.id_goodsname AND g.id_typename=a.id_typename AND g.stock_id=b.stock_id')
    		//->join('wh_user h ON b.user_id=h.id')
    	//排列根据出库单据号和出库时间
    	->order("a.id_enter desc,b.enterdate asc")
    	->where($map)
    	->select();
    	
    	
//     	var_dump($model_enterstockdetails->getlastsql());
    	 
    	$this->assign("data",$data);
    
    	$this->display();
    }
    
    
	
    //添加
	public function add(){
		if(IS_POST){
// 			dump(I('post.'));
// 			die;
			//创建模型
			$model_enterstockdetails = D('Enterstockdetails');
			if($model_enterstockdetails->create()){
				if($model_enterstockdetails->add()){
					$this->success("添加成功！",U("index"));
					exit;
				}
				else{
					if(APP_DEBUG){
						$sql = $model_enterstockdetails->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			//表单验证失败
			else{
				$error = $model_enterstockdetails->getError();
				$this->error(implode("<br/>",$error));
			}
		}
		
		//显示表单
		$this->display();
	}
	
	
	//编辑
	public function edit($id){
		$model_enterstockdetails = D('Enterstockdetails');
		
		$data = $model_enterstockdetails
		->alias('a')
		->field('a.*,b.name AS name_goods,c.name AS name_type,d.stock_id')
		->join("wh_goodsname b ON b.id=a.id_goodsname")
		->join("wh_typename c ON c.id=a.id_typename")
		->join("wh_enterstock d ON d.id=a.id_enter")
		->where("a.id={$id}")->find();
		
		$this->assign("data",$data);
		
		$this->display();
	}
	
	public function update(){
		if(IS_POST){
// 			dump(I('post.'));
// 			die;
			$model_enterstockdetails = D('Enterstockdetails');
			if($model_enterstockdetails->create()){
				$result = $model_enterstockdetails->save();
				if($result!==FALSE)
					$this->success("修改数据成功！",U('index'));
				else 
				{
					if(APP_DEBUG){
						$sql = $model_enterstockdetails->getLastSql();
						$this->error("修改数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("修改数据失败，请重试");
				}
			}
			else{
				//返回一个数组，拼装成字符串
				$error = $model_enterstockdetails->getError();
				$this->error(implode("<br/>",$error));
			}
		}
	}
	
	//删除
	public function del($id){
		$model_enterstockdetails = D('Enterstockdetails');
		$model_stock = M('stock');
		
		$details = $model_enterstockdetails
		->alias('a')
		->field('a.*,b.quantity_stock,b.id bid')
		->join("wh_stock b ON b.id_goods=a.id_goods AND b.stock_id=a.stock_id")
		->where("a.id IN ($id)")
		->select();
		
		//修改库存
		foreach($details as $k=>$v){
			$quantity = $v['quantity_stock']-$v['quantity_enter'];
			
			$data = array(
					'quantity_stock'=>$quantity,
					'time_update'	=>date("Y-m-d H:i:s",time())
			);
			
			$model_stock
			->where("id={$v['bid']}")
			->setField($data);	
		}
		
		//删除入库详情表记录
		$result = $model_enterstockdetails->delete($id);
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
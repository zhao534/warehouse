<?php
namespace Home\Model;
use Think\Model;
class EnterstockModel extends Model 
{
	protected $patchValidate = true;
	
	protected $_validate = array(
		array('enterdate', 'require', '入库时间不能为空！', 1),
		array('user_id', 'chkfunc', '未选择入库人！', 3,"callback"),
		array('stock_id', 'chkfunc', '未选择所入库房！', 3,"callback"),
		array('goods_id', 'chkfunc', '未选择商品名称！', 3,"callback"),
		//一组select验证下述失效
		//array('goods_id[]', 'require', '必须要选择商品名称！', 1),
		array('price_enter', 'chkfunc', '未输入商品价格！', 3,"callback"),
		array('quantity_enter', 'chkfunc', '未输入商品数量！', 3,"callback"),

		//添加的商品不能重复，判断条件：商品ID，库房ID
		array('goods_id', 'chkgoods_stock', '所选的库房中已有此商品', 3,"callback"),	
	); 
	
	
	public function chkfunc($value){
		//1.入库人2.商品名称未选择、库房未选择3.商品价格、数量未选择
		if($value=="0" || in_array("0",$value) || in_array("", $value))
			return false;
		else return true;
	}
	
	public function chkgoods_stock($v){
		$data = I('post.');
		//$data['goods_id'] == $v
		$data_stock = $data['stock_id'];
		$number = count($data_stock);
		
		for($i=0;$i<$number;$i++){
			for($j=$i;$j<$number;$j++){
				if(($i!=$j) && ($data_stock[$i]==$data_stock[$j]) && ($v[$i]==$v[$j]))
					return false;
			}
		}
	}
	
	
	//钩子函数
	//入库表数据插入后，
	//1.进行入库明细表插入操作
	//2.库存表修改对应的库存记录
	public function _after_insert(&$data, $options){
		
		$details= array();
		$details['id_enter'] = $data['id'];
		
		$model_enterstockdetails = M('enterstockdetails');
		$model_stock = M('stock');
		
		$data_post = I('post.');
		
		for($i=0;$i<count($data_post['goods_id']);$i++){
			$details['stock_id'] 	   = $data_post['stock_id'][$i];
			$details['id_goods']	   = $data_post['goods_id'][$i];
			$details['price_enter']	   = $data_post['price_enter'][$i];
			$details['quantity_enter'] = $data_post['quantity_enter'][$i];
			
			//1.进行入库明细表插入操作
			$model_enterstockdetails->add($details);
			
			//2.库存表修改对应的库存记录
			$stock= array();
			$stock['price_enter']    = $details['price_enter'];
			$stock['quantity_stock'] = $details['quantity_enter'];
			$stock['stock_id']       = $details['stock_id'];
			$stock['id_goods']	     = $details['id_goods'];
			$stock['time_update']    = date("Y-m-d H:i:s",time());
			
			//库存表没有此记录就添加，否则直接修改库存记录信息
			//查找条件：商品ID，仓库ID，
			$v = $model_stock->field('id,quantity_stock')->where("id_goods={$stock['id_goods']} AND stock_id={$stock['stock_id']}")->find();
			
			if($v['id']>0){
				$stock['quantity_stock'] += $v['quantity_stock'];
				$stock['id'] 			  = $v['id'];
				$model_stock->save($stock);
			}
			else if($v['id']==NULL) $model_stock->add($stock);
		}
	}
	
	//编辑入库单
	//先读取入库单对应的入库详情信息，删除对应的入库详情单信息，然后将编辑的信息重新添加，修改库存信息；
	
	//1.单纯编辑已有的商品信息、修改已有入库详情单信息、修改对应库存即可；
	//2.添加新的商品信息、添加新的入库详情单对应信息、添加新的对应库存信息；
	//3.删除已有的商品信息、删除入库详情单对应信息、删除对应库存信息。
	public function _before_update(&$data, $options){
		$data_post = I('post.');
		$data_goods_stock = ','.$data_post['data_goods_stock'];
		
		$model_enterstockdetails = M('enterstockdetails');
		$model_stock = M('stock');
		
		//入库明细表：
		$details= array();
		$details['id_enter'] = $data['id'];
		
		dump($data_goods_stock);
		die;
		
		for($i=0;$i<count($data_post['goods_id']);$i++){
			$details['stock_id'] 	   = $data_post['stock_id'][$i];
			$details['id_goods']	   = $data_post['goods_id'][$i];
			$details['price_enter']	   = $data_post['price_enter'][$i];
			$details['quantity_enter'] = $data_post['quantity_enter'][$i];
			
			//1.进行入库明细表操作
			$str = ','.$details['id_goods'].'+'.$details['stock_id'];
			$v = strpos($data_goods_stock, $str);
			//添加了新的商品库房信息
			if($v===false){
				
			}
			//
			else if($v>=0){
				
			}
			$model_enterstockdetails->add($details);
		}
	}
	
	
	//钩子函数（删除）
	//删除入库表记录前，将对应的入库详细表记录删除
	//对库存数量造成的影响？？？
	public function _before_delete($options){
		
	}
	
	//分页显示
	public function show_page($perpage){
		//查询条件
		$map = 1;
		
		//单据号
		if($order = I('get.order')){
			$map .= " AND a.id={$order}";
		}
		
		//时间段
		if($end = I('get.end')){
			$start = I('get.start');
				
			if(empty($start))
				$map .= " AND UNIX_TIMESTAMP('{$end}')>=UNIX_TIMESTAMP(a.enterdate)";
			else
				$map .= " AND UNIX_TIMESTAMP('{$start}')<=UNIX_TIMESTAMP(a.enterdate) AND UNIX_TIMESTAMP('{$end}')>=UNIX_TIMESTAMP(a.enterdate)";
		}
		
		//入库人
		if($user = I('get.user')){
			$map .= " AND b.username like '%{$user}%'";
		}
		
		
		$totalRows = $this->where($map)->count();
		
		$page = new \Think\Page($totalRows,$perpage);
		
		$page->setConfig("prev", "上一页");
		$page->setConfig("next", "下一页");
		$page->setConfig("first", "首页");
		$page->setConfig("last", "末页");
		
		//分页显示输出
		$str = $page->show();
		
		//分页数据查询
		$data =$this->alias('a')
		->field('
				a.*,
				b.id AS id_user,b.username
				')
		->join('wh_user b ON a.user_id=b.id')
		->where($map)->limit($page->firstRow.','.$page->listRows)->order('a.enterdate desc')->select();
		
//  		var_dump($this->getLastSql());
		
		return array(
				"data" => $data,
				"str"  => $str
				);
	}
}
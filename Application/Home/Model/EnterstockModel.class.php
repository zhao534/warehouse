<?php
namespace Home\Model;
use Think\Model;
class EnterstockModel extends Model 
{
	protected $patchValidate = true;
	
	protected $_validate = array(
		array('enterdate','require', '入库时间不能为空！', 1),
		array('user_id', 'chkfunc', '未选择入库人！', 3,"callback"),
		array('stock_id', 'chkfunc', '未选择所入库房！', 3,"callback"),
//  	array('goods_name', 'chkfunc', '商品名称未填写！', 3,"callback"),
 		array('type_name', 'chkfunc', '型号未填写！', 3,"callback"),	
		array('price_enter', 'chkfunc', '未输入商品价格！', 3,"callback"),
		array('quantity_enter', 'chkfunc', '未输入商品数量！', 3,"callback"),	
		//添加的商品不能重复，判断条件：商品名称，型号名称
		array('goods_name', 'chkGoodsAndType', '商品名称未填写或者商品名称、型号重复！', 3,"callback"),
	); 
	
	
	public function chkfunc($value){
		//1.入库人2.库房未选择3.商品价格、数量未选择
		if($value=="0" || in_array("0",$value) || in_array("", $value))
			return false;
		else return true;
	}
	
	public function chkGoodsAndType($v){
// 		dump($v);
		$data = I('post.');
		
		if(in_array("", $v))
			return false;
		
		//$data['goods_name'] == $v
		$data_type = $data['type_name'];
		$number = count($v);
		
		for($i=0;$i<$number;$i++){
			for($j=$i;$j<$number;$j++){
				if(($i!=$j) && ($data_type[$i]==$data_type[$j]) && ($v[$i]==$v[$j]))
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
		$model_stock             = M('stock');
		$model_goodsname 		 = M('Goodsname');
		$model_typename  		 = M('typename');
		
		$data_post = I('post.');
		
		for($i=0;$i<count($data_post['goods_name']);$i++){
			$details['stock_id'] 	   = $data_post['stock_id'];
			$details['id_goodsname']   = $data_post['goods_name'][$i];
			$details['id_typename']    = $data_post['type_name'][$i];
			$details['price_enter']	   = $data_post['price_enter'][$i];
			$details['quantity_enter'] = $data_post['quantity_enter'][$i];
			
			//单位
			if($data_post['unit'][$i]){
				$details['unit'] 	   = $data_post['unit'][$i];
			}
			
			//1.插入商品名称备选表和型号名称备选表
			//根据Name获取表的id值
			$id_goods = $model_goodsname->getFieldByName($details['id_goodsname'],'id');
			if($id_goods)
				$details['id_goodsname'] = $id_goods;
			else
				$details['id_goodsname'] = $model_goodsname->add(array('name'=>$details['id_goodsname'])); 
			
			$id_type = $model_typename->getFieldByName($details['id_typename'],'id');
			if($id_type)
				$details['id_typename'] = $id_type;
			else
				$details['id_typename'] = $model_typename->add(array('name'=>$details['id_typename']));
			
			
			
			//2.进行入库明细表插入操作
			$model_enterstockdetails->add($details);
			
			//3.库存表修改对应的库存记录
			$stock= array();
			$stock['price_enter']    = $details['price_enter'];
			$stock['quantity_stock'] = $details['quantity_enter'];
			$stock['stock_id']       = $details['stock_id'];
			$stock['id_goodsname']	 = $details['id_goodsname'];
			$stock['id_typename']	 = $details['id_typename'];
			$stock['time_update']    = date("Y-m-d H:i:s",time());
			
			if(isset($details['unit']))
				$stock['unit'] = $details['unit'];
			
			//库存表没有此记录就添加，否则直接修改库存记录信息
			//查找条件：商品ID，仓库ID，
			$v = $model_stock
			->field('id,quantity_stock')
			->where("
					id_goodsname={$stock['id_goodsname']} AND 
					id_typename={$stock['id_typename']} AND 
					stock_id={$stock['stock_id']}
					")
			->find();
			
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
		//现任数据
		$data_post = I('post.');
		
		$model_enterstockdetails = M('enterstockdetails');
		$model_stock 			 = M('stock');
		$model_goodsname 		 = M('Goodsname');
		$model_typename 		 = M('typename');
		
		//获取入库详情单和库存记录
		//原有数据
		$value = $model_enterstockdetails->alias('a')->field('
				a.*,
				c.quantity_stock,c.stock_id
				')
				->join('wh_enterstock b ON b.id=a.id_enter')
				->join('wh_stock c ON 
						b.stock_id=c.stock_id AND 
						a.id_goodsname=c.id_goodsname AND 
						a.id_typename=c.id_typename')
				->where("a.id_enter={$data_post['id']}")
				->select();
		
// 		dump($data_post);
// 		var_dump($model_enterstockdetails->getLastSql());
//  	dump($value);
//  	die;
		
		//将入库单对应的原有入库详情单记录删除
		$result = $model_enterstockdetails
		->where("id_enter={$data_post['id']}")
		->delete();
		
		if($result){
			//删除成功
			//入库明细表数组
			$details= array();
			$details['id_enter'] = $data_post['id'];
			//将现任入库详情单数据重新添加
			for($i=0;$i<count($data_post['goods_name']);$i++){
				$details['stock_id'] 	   = $data_post['stock_id'];
				$details['id_goodsname']   = $data_post['goods_name'][$i];
				$details['id_typename']    = $data_post['type_name'][$i];
				$details['price_enter']	   = $data_post['price_enter'][$i];
				$details['quantity_enter'] = $data_post['quantity_enter'][$i];	
				
				//单位
				if($data_post['unit'][$i]){
					$details['unit'] 	   = $data_post['unit'][$i];
				}
				
				//1.插入商品名称备选表和型号名称备选表
				//根据Name获取表的id值
				$id_goods = $model_goodsname->getFieldByName($details['id_goodsname'],'id');
				if($id_goods)
					$details['id_goodsname'] = $id_goods;
				else
					$details['id_goodsname'] = $model_goodsname->add(array('name'=>$details['id_goodsname'])); 
				
				$id_type = $model_typename->getFieldByName($details['id_typename'],'id');
				if($id_type)
					$details['id_typename'] = $id_type;
				else
					$details['id_typename'] = $model_typename->add(array('name'=>$details['id_typename']));
				
				
				//2.进行入库明细表添加操作
				$model_enterstockdetails->add($details);
				//3.修改对应库存记录
				$stock = array();
				$stock['stock_id'] 	     = $details['stock_id'];
				$stock['id_goodsname']	 = $details['id_goodsname'];
				$stock['id_typename']    = $details['id_typename'];
				$stock['price_enter']	 = $details['price_enter'];
				$stock['time_update']    = date("Y-m-d H:i:s",time());
				//$stock['quantity_enter']
				//$stock['unit']

				if(isset($details['unit'])){
					$stock['unit'] = $details['unit'];
				}
				
				$flag = -1;
				foreach ($value as $k=>$v){
					if(($v['stock_id']==$details['stock_id']) && ($v['id_goodsname']==$details['id_goodsname']) && ($v['id_typename']==$details['id_typename']))
						$flag=$k;
				}
				
				if($flag==-1){
					//不存在，则插入新的库存记录
					//计算改变后的库存
					$stock['quantity_stock'] = $details['quantity_enter'];
					$model_stock->add($stock);
				}
				else{
					//库存记录已有，修改此记录
					//计算改变后的库存
					$stock['quantity_stock'] = $value[$flag]['quantity_stock']-$value[$flag]['quantity_enter']+$details['quantity_enter'];
					$model_stock
					->where("
							id_goodsname={$details['id_goodsname']} AND 
							id_typename={$details['id_typename']} AND 
							stock_id={$details['stock_id']}
							")
					->setField(array(
							'price_enter'   =>$stock['price_enter'],
							'quantity_stock'=>$stock['quantity_stock'],
							'time_update'	=>date("Y-m-d H:i:s",time()),
							'unit'			=>$stock['unit'],
					));
					
					//unset既可以删除变量，也可以删除数组中某个单元。但要注意的是，数组不会重建索引.
					unset($value[$flag]);
				}
			}
			
			//删除了入库详情记录，库存改变
			if($value){
				foreach ($value as $k=>$v){
					$quantity = $v['quantity_stock']-$v['quantity_enter'];
					$model_stock
					->where("
							id_goodsname={$v['id_goodsname']} AND 
							id_typename={$v['id_typename']} AND 
							stock_id={$v['stock_id']}
							")
					->setField(array(
							'quantity_stock'=>$quantity,
							'time_update'	=>date("Y-m-d H:i:s",time()),
					));
				}
			}//删除计算结束
		}
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
		
		//所入库房
		if($stock = I('get.stock')){
			$map .= " AND a.stock_id={$stock}";
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
				b.id AS id_user,b.username,
				c.name as stockname
				')
		->join('wh_user b ON a.user_id=b.id')
		//限定当前用户只能看到自己的库房
		->join('wh_stockhouse c ON c.id=a.stock_id AND FIND_IN_SET('.session('userid').',c.user_id_list)')
		->where($map)
		->limit($page->firstRow.','.$page->listRows)
		->order('a.enterdate desc')
		->select();
		
   		//var_dump($this->getLastSql());
		
		return array(
				"data" => $data,
				"str"  => $str
				);
	}
}
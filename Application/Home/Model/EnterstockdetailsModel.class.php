<?php
namespace Home\Model;
use Think\Model;
class EnterstockdetailsModel extends Model 
{
	protected $patchValidate = true;
	
	protected $_validate = array(
					array('id_enter', 'require', '入库单编号不能为空！', 1),
					array('id_enter', 'chkorder', '入库单号不存在，请确认！', 3,"callback",3),
					
					//只添加操作时验证
					array('id_enter', 'chkorder_goods_type', '数据库中已有此记录，不用再添加！', 3,"callback",1),
					
					array('id_goodsname', 'require', '必须要填写商品名称！', 1),
					//array('id_goods', 'chkgoods', '必须要选择商品名称！', 3,"callback"),
					//array('stock_id', 'chkstock', '必须要选择所入库房！', 3,"callback"),
					array('id_typename', 'require', '必须要填写型号名称', 1),

					array('price_enter', 'require', '进货价不能为空！', 1),
					array('quantity_enter', 'require', '进货数量不能为空！', 1),
					//array('total', 'require', '进货总价不能为空！', 1),				
					array('price_enter', 'chkprice', '进货价必须是合法的数字！', 3,"callback"),
					array('quantity_enter', 'chkquantity', '进货数量必须大于0而且是整数！', 3,"callback"),
	);
	
	//检测数据库中是否有此单号，没有则报错
	public function chkorder($v){
		if($this->field('id')->where("id_enter={$v}")->select())
			return true;
		return false;
	}
	
	//入库详细表中有相同的单号、商品名称、型号（三个条件），则不插入，报错
	public function chkorder_goods_type($v){
		$post = I("post.");
		$id_goodsname = $post['id_goodsname'];
		$id_typename = $post['id_typename'];
		
		$res = $this
		->alias('a')
		->field('a.id')
		->where("a.id_enter={$v} AND b.name='{$id_goodsname}' AND c.name='{$id_typename}'")
		->join("wh_goodsname b ON b.id=a.id_goodsname")
		->join("wh_typename c ON c.id=a.id_typename")
		->select();
		
// 		var_dump($this->getLastSql());
// 		die;
		
		if($res)
			return false;
		return true;
	}
	
	public function chkgoods($v){
		return isSelected($v);
	}
	
	public function chkstock($v){
		return isSelected($v);
	}

	public function chkquantity($v){
		return IsPositiveInteger($v);
	}
	
	public function chkprice($v){
		return IsPositiveFloat($v);
	}
	
	//钩子函数
	//在入库明细表插入前，进行名称备选表和型号备选表操作
	public function _before_insert(&$data, $options){	
		$post = I('post.');
		
		//!!!注意：$data['id_goodsname']的值为0，$post['id_goodsname']的值为'电脑'
		
		$model_goodsname = M('Goodsname');
		$model_typename  = M('typename');
		
		//根据Name获取表的id值
		$id_goods = $model_goodsname->getFieldByName($post['id_goodsname'],'id');
		
		if($id_goods)
			$data['id_goodsname'] = $id_goods;
		else
			$data['id_goodsname'] = $model_goodsname->add(array('name'=>$post['id_goodsname']));
		
		//根据Name获取表的id值
		$id_type = $model_typename->getFieldByName($post['id_typename'],'id');
		if($id_type)
			$data['id_typename'] = $id_type;
		else
			$data['id_typename'] = $model_typename->add(array('name'=>$post['id_typename']));
		
		//单位字段设置默认值，如果用户没有输入，则使用默认值。
		if(empty($data['unit']))
			unset($data['unit']);
	}
	
	//读取goodsname和typename的id
	public function _before_update(&$data, $options){
		$this->_before_insert($data, $options);
	}
	
	//根据goodsname和typename获取对应的ID
	//$data收集表单结果
	//$data前的&
	//private function getIDByName(&$data){}
	
	//入库明细表插入后，库存表修改对应的库存记录
	public function _after_insert(&$data, $options){	
		$data_post = I('post.');
// 		dump($data_post);
// 		die;
		$model_stock = M('stock');
		
		$details= array();
		$details['price_enter']    = $data['price_enter'];
		$details['quantity_stock'] = $data['quantity_enter'];
		$details['id_goodsname']   = $data['id_goodsname'];
		$details['id_typename']    = $data['id_typename'];
		$details['time_update']    = date("Y-m-d H:i:s",time());
		//$details['stock_id']
		
		//获取仓库索引
		$stock_id = $this
		->alias('a')
		->field('b.stock_id')
		->join('wh_enterstock b ON b.id=a.id_enter')
		->where("a.id_enter={$data['id_enter']} AND a.id_goodsname={$data['id_goodsname']} AND a.id_typename={$data['id_typename']}")
		->find();
		
		$details['stock_id'] = $stock_id['stock_id'];
		
		//库存表没有此记录就添加，否则直接修改库存记录信息
		//查找条件：商品ID，仓库ID，
		$v = $model_stock
		->field('id,quantity_stock')
		->where("id_goodsname={$details['id_goodsname']} AND id_typename={$details['id_typename']} AND stock_id={$details['stock_id']}")
		->find();
		
		if($v['id']>0){
			$details['quantity_stock'] += $v['quantity_stock'];
			$details['id'] 				= $v['id'];
			$model_stock->save($details);
		}
		else if($v['id']==NULL) $model_stock->add($details);	
	}
	
	//更新库存
	public function _after_update(&$data, $options){
		$data_post = I('post.');

		$model_stock = M('stock');
		
		$details= array();
		$details['price_enter']    = $data_post['price_enter'];
		$details['quantity_stock'] = $data_post['quantity_enter'];
		$details['stock_id']       = $data_post['stock_id'];
		//此处就得用$data['id_goodsname']，不能用$data_post['id_goodsname_old']
		$details['id_goodsname']   = $data['id_goodsname'];
		$details['id_typename']    = $data['id_typename'];
		$details['time_update']    = date("Y-m-d H:i:s",time());
		
		//计算库存
		//库存表没有此记录就添加，否则直接修改库存记录信息
		//查找条件：商品ID，仓库ID
		$v = $model_stock
		->field('id,quantity_stock')
		->where("id_goodsname={$details['id_goodsname']} AND id_typename={$details['id_typename']} AND stock_id={$details['stock_id']}")
		->find();
		
		//如果商品名称或型号有变更，就读取原有信息
		if( ($data['id_goodsname']!=$data_post['id_goodsname_old']) || ($data['id_typename']!=$data_post['id_typename_old']) ){
			$p =$model_stock
				->field('id,quantity_stock')
				->where("id_goodsname={$data_post['id_goodsname_old']} AND id_typename={$data_post['id_typename_old']} AND stock_id={$details['stock_id']}")
				->find();
		}
		
		if($v['id']>0){
			//变更的不是商品自身属性
			if(isset($p)==false){
				//【当前库存数量】=【原有库存】+【当前修改过的进货数量】-【修改前的进货数量】
				$details['quantity_stock']  = $v['quantity_stock']+$details['quantity_stock']-$data_post['quantity_old'];
				$details['id'] 				= $v['id'];
				$model_stock->save($details);
			}
			//商品属性变更了
			else{
				//【变更商品的库存数量】=【原有库存】+【修改过的进货数量】
				$model_stock->save(array(
						'quantity_stock' => $v['quantity_stock']+$data_post['quantity_enter'],
						'id'			 => $v['id'],
				));
				//【以前商品的库存数量】=【原有库存】-【提交前的进货数量】
				$model_stock->save(array(
						'quantity_stock' => $p['quantity_stock']-$data_post['quantity_old'],
						'id'			 => $p['id'],
				));
			}
		}
		else if($v['id']==NULL){
			//修改原有库存	
			//【以前商品的库存数量】=【原有库存】-【提交前的进货数量】
			$model_stock->save(array(
					'quantity_stock' => $p['quantity_stock']-$data_post['quantity_old'],
					'id'			 => $p['id'],
			));
		
			//添加新库存
			$model_stock->add($details);
		}
		
		
	}
	
	/*
	public function _before_update(&$data, $options){

		$data_post = I('post.');
		
		$model_stock 	 = M('stock');
		$model_goodsname = M('Goodsname');
		$model_typename  = M('typename');
		
		$details= array();
		$details['price_enter']    = $data_post['price_enter'];
		$details['quantity_stock'] = $data_post['quantity_enter'];
		$details['stock_id']       = $data_post['stock_id'];
		//$details['id_goodsname']   = $data_post['id_goodsname'];
		//$details['id_typename']    = $data_post['id_typename'];
		$details['time_update']    = date("Y-m-d H:i:s",time());
		
		//根据Name获取表的id值
		$id_goods = $model_goodsname->getFieldByName($data_post['id_goodsname'],'id');
		
		if($id_goods)
			$details['id_goodsname'] = $id_goods;
		else
			$details['id_goodsname'] = $model_goodsname->add(array('name'=>$data_post['id_goodsname']));
		
		//根据Name获取表的id值
		$id_type = $model_typename->getFieldByName($data_post['id_typename'],'id');
		if($id_type)
			$details['id_typename'] = $id_type;
		else
			$details['id_typename'] = $model_typename->add(array('name'=>$data_post['id_typename']));

		
		//库存表没有此记录就添加，否则直接修改库存记录信息
		//查找条件：商品ID，仓库ID
		$v = $model_stock->field('id,quantity_stock')->where("id_goodsname={$details['id_goodsname']} AND id_typename={$details['id_typename']} AND stock_id={$details['stock_id']}")->find();
		
		if($v['id']>0){
			//读取修改前的入库详细单中的进货数量
			//$old = $this->where("id={$options['where']['id']}")->getField('quantity_enter');
			//然后【当前库存数量】=【原有库存】+【当前修改过的进货数量】-【修改前的进货数量】
			$details['quantity_stock']  = $v['quantity_stock']+$details['quantity_stock']-$data_post['quantity_old'];
			$details['id'] 				= $v['id'];
			$model_stock->save($details);
		}
		else if($v['id']==NULL){
			//修改原有库存
			$p = 
			$model_stock
			->field('id,quantity_stock')
			->where("id_goodsname={$data_post['id_goodsname_old']} AND id_typename={$data_post['id_typename_old']} AND stock_id={$details['stock_id']}")
			->find();
			
			if($p['id'])
				$model_stock->save(array(
						'quantity_stock' => $p['quantity_stock']-$data_post['quantity_old'],
						'id'			 => $p['id'],
				));
	
			//添加新库存
			$model_stock->add($details);
		}
		
		//最后一步，将id传给data['id_goodsname']
		$data['id_goodsname'] = $details['id_goodsname'];
		$data['id_typename']  = $details['id_typename'];
	}
	*/
	
	//分页显示
	public function show_page($perpage){
		//查询条件
		$map = 1;
		
		//搜索条件：
		//订单号
		if($order = I('get.order')){
			$map .= " AND a.id_enter={$order}";
		}
	
		//商品库房
		if($stock = I('get.stock')){
			$map .= " AND e.stock_id={$stock}";
		}
		
		//商品分类
		//搜索时显示此分类下所有的
		if($cat = I('get.cat')){
			//获取当前分类的子分类
			$model_category = D('category');
			$children = $model_category->getChildren($cat);
		
			if($children){
				$children = implode(',', $children);
				//加上当前分类
				$str = $cat.','.$children;
			}
			else
				$str = $cat;
				
			$map .= " AND d.id IN({$str})";
		}
		
		//时间段
		if($end = I('get.end')){
			$start = I('get.start');
			
			if(empty($start))
				$map .= " AND UNIX_TIMESTAMP('{$end}')>=UNIX_TIMESTAMP(e.enterdate)";
			else
				$map .= " AND UNIX_TIMESTAMP('{$start}')<=UNIX_TIMESTAMP(e.enterdate) AND UNIX_TIMESTAMP('{$end}')>=UNIX_TIMESTAMP(e.enterdate)";
		}
		
		//商品类型
		if($type = I('get.type')){
			$map .= " AND c.name LIKE '%{$type}%'";
		}
		
		//商品名称
		if($name = I('get.name')){
			$map .= " AND b.name LIKE '%{$name}%'";
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
				b.name as name_goods,
				c.name as name_type,
				e.stock_id,e.enterdate,
				f.name as name_stockhouse
				')
		->join('wh_goodsname b ON a.id_goodsname=b.id')
		->join('wh_typename c ON a.id_typename=c.id')
		//->join('wh_category d ON b.cat_id=d.id')
		->join('wh_enterstock e ON a.id_enter=e.id ')
		->join('wh_stockhouse f ON e.stock_id=f.id AND FIND_IN_SET('.session('userid').',f.user_id_list)')
		//排列根据入库单据号和入库时间
		->order("a.id_enter desc")
		->where($map)->limit($page->firstRow.','.$page->listRows)->select();
		
// 		var_dump($this->getLastSql());
		
// 		dump($data);
		
		return array(
				"data" => $data,
				"str"  => $str
				);
	}
}
<?php
namespace Home\Model;
use Think\Model;
class StockModel extends Model 
{
	protected $patchValidate = true;
	
	protected $_validate = array(
					array('id_goods', 'chkgoods', '必须要选择商品名称！', 3,"callback"),
					array('quantity_stock', 'chkquantity', '商品数量必须大于0！', 3,"callback"),
					array('id_goods', 'require', '必须要选择商品名称！', 1),
					//array('time_update', 'require', '更新库存时间不能为空！', 1),
					//array('operate', 'require', '上次操作不能为空！', 1),
			); 

	public function chkgoods($value){
		if($value)
			return true;
		else return false;
	}
	
	public function chkquantity($value){
		if($value>0)
			return true;
		else return false;
	}	
	
	//钩子函数
	//在插入之前，将模块名称，控制器名称，方法名称首字母都大写
	public function _before_insert(&$data, $options){
		$data['time_update'] = date("Y-m-d H:i:s",time());
	}
	
	public function _before_update(&$data, $options){
		$this->_before_insert($data, $options);
	}
	
	//分页显示
	public function show_page($perpage){
		//查询条件
		$map = 1;
		
		//搜索条件：
		//商品库房
		if($stock = I('get.stock')){
			$map .= " AND c.id={$stock}";
		}
		
		//商品分类
		//搜索时显示此分类下所有的
		/*
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
				
			$map .= " AND e.id IN({$str})";
		}
		*/
		
		//商品类型
		if($type = I('get.type')){
			$map .= " AND d.name LIKE '%{$type}%'";
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
				c.name as name_stockhouse,
				d.name as name_type
				')
		->join('wh_goodsname b ON a.id_goodsname=b.id')
		//限定当前用户只能看到自己的库房
		->join('wh_stockhouse c ON a.stock_id=c.id AND FIND_IN_SET('.session('userid').',c.user_id_list)')
		->join('wh_typename d ON a.id_typename=d.id')
		->where($map)
		->limit($page->firstRow.','.$page->listRows)
		->select();

// 		var_dump($this->getLastSql());
		
		return array(
				"data" => $data,
				"str"  => $str
				);
	}
	
	
	public function getDataByEdit($id){
		
		$data = $this->alias('a')
		->field('
				a.*,
				c.id as id_cat,c.name as name_cat,
				b.name
				')
		->join("wh_goods b ON a.id_goods=b.id")
		->join("wh_category c ON c.id=b.cat_id")
		->where("a.id={$id}")->select();
		
		//var_dump($this->getLastSql());
		
		return $data;
	}
}
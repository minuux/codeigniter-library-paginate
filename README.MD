#codeigniter 分页类

##require
- helper:url
- database

##使用

```
$this->load->library('paginate');

$this->db->start_cache();//需要缓存where语句，用于统计总行数

$this->db->where('field_name','field_value');
$this->paginate->filter(array('nickname'));
$this->db->from('view_recruits');

$this->db->stop_cache();

$this->db->order_by('id');//分页，必须指定排序规则
$this->paginate->get();
```

####返回的对象$this->paginate->get()为一个对象
```
  {
       'total' => int,//总行数
       'per_page' => int,//每页显示的行
       'cur_page' => int,//当前页码
       'num_page' =>int,//总页数
       'data' => array(),//结果集
       'links' => string//$this->pagination->create_links()返回的字符串
  }
```

##配置filter
```
$filter=array(
'title',//简单列出字段名，将会使用默认值field_type:string,field_name:title,field_match:fuzzy,也可以像下面的字段详细的设置
'user_id'=>array(
         'field_type'=>'number',//只区分了两种number|string,满足type的时候才会添加到where语句中
         'field_name'=>'user_id',//字段在sql中的名称，使用join的时候，可以写成 table1.user_id 来指定
         'field_match'=>'precise' //match有两种，精准(precise)|模糊(fuzzy)查询两种
        )
)

```
 - 精准(precise):通过在查询的值前后添加*号来模糊查询,比如 内容* 类似 like '内容%',也可以通过*,*查询多个值.
   内容1,内容2 类似 in('内容1','内容2')

 - 模糊(fuzzy):效果类似 like '%内容%'

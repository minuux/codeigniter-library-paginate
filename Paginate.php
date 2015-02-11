<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class CI_Paginate {

    protected $cur_page_segment = 'page';
    protected $per_page_segment = 'per_page';
    protected $per_page = 10;
    protected $per_page_max = 10;
    protected $CI;
    protected $input_get;

    function __construct($config = array()) {
        $this->CI = & get_instance();
        $this->initialize($config);
    }

    public function initialize($config = array()) {
        foreach ($config as $key => $val) {
            if (isset($this->$key)) {
                $this->$key = $val;
            }
        }

        $this->input_get = $this->CI->input->get();

        if (!isset($this->input_get[$this->cur_page_segment])) {
            $this->input_get[$this->cur_page_segment] = 1;
        }
        if (!isset($this->input_get[$this->per_page_segment])) {
            $this->input_get[$this->per_page_segment] = $this->per_page;
        }

        return $this;
    }

    /*
     * 过滤URL中设置的过滤参数
     * 还需要判断查询值的类型，如果是数字类型的话就需要将非数字类型去除
     * 或者如果数字类型的查询就额外的代码处理，让查询值不会直接被数据库使用
     */

    function filter($filters = array()) {

        if (count($this->input_get) > 0) {

            foreach (array_intersect_key($this->_format_filter($filters), $this->input_get) as $field_name => $field_config) {
                $field_value = $this->input_get[$field_name];
                if ($field_value == NULL) {
                    continue;
                }
                //使用 模糊或精准配置CI的查询条件
                $this->$field_config['field_match']($field_value, $field_config);
            }
        }
    }

    /**
      将键值为数字的用值去替换键名
      增加配置，用于当数据查询使用到join的时候可以指定出url中查询的字段内容使用的具体表名
     * */
    private function _format_filter($filters) {
        $result = array();
        foreach ($filters as $key => $value) {
            if (is_numeric($key)) {
                $result[$value] = array('field_name' => $value, 'field_type' => 'string', 'field_match' => 'fuzzy'); //模糊查询
            } else {
                $result[$key] = array(
                    'field_name' => isset($value['field_name']) ? $value['field_name'] : $key,
                    'field_type' => isset($value['field_type']) ? $value['field_type'] : 'string',
                    'field_match' => isset($value['field_match']) ? $value['field_match'] : 'precise'//fuzzy 模糊，precise 精准
                );
            }
        }
        return $result;
    }

    /**
      模糊查询
     */
    function fuzzy($str, $config) {
        $this->CI->db->like($config['field_name'], $str, 'both');
    }

    /**
      精准查询
     */
    function precise($str, $config) {

        if (strstr($str, ',')) {
            $this->CI->db->where_in($config['field_name'], array_map('strval', preg_split('/,/', $str)));
        } elseif (strstr($str, '*')) {
            //如果是模糊查询
            if (substr($str, 0, 1) == '*' && substr($str, -1, 1) != '*') {
                $this->CI->db->like($config['field_name'], preg_replace('/\*/', '', $str), 'before');
            } elseif (substr($str, 0, 1) != '*' && substr($str, -1, 1) == '*') {
                $this->CI->db->like($config['field_name'], preg_replace('/\*/', '', $str), 'after');
            } else {
                $this->CI->db->like($config['field_name'], preg_replace('/\*/', '', $str), 'both');
            }
        } elseif ($config['field_type'] == 'number' AND is_numeric($str)) {
            $this->CI->db->where($config['field_name'], $str);
        }
    }

    function get() {

        //使其可以在URL中传递参数来设置每页显示的数据量
        $per_page = (is_numeric($this->input_get[$this->per_page_segment]) && $this->input_get[$this->per_page_segment] > 0 && $this->input_get[$this->cur_page_segment] < $this->per_page_max) ? $this->input_get[$this->per_page_segment] : $this->per_page;

        //获取当前浏览页
        $page = (is_numeric($this->input_get[$this->cur_page_segment]) && $this->input_get[$this->cur_page_segment] > 0) ? $this->input_get[$this->cur_page_segment] : 1;

        //获取数据
        $this->CI->db->limit($per_page, ($page - 1) * $per_page);
        $query_data = $this->CI->db->get();

        //获取总行数
        $total_rows = $this->CI->db->count_all_results();
        //清理掉cache
        $this->CI->db->flush_cache();

        //去除url中设置页码的参数
        unset($this->input_get[$this->cur_page_segment]);


        $this->CI->load->library('pagination');
        $this->CI->pagination->initialize(array(
            'total_rows' => $total_rows,
            'per_page' => $per_page,
            'use_page_numbers' => TRUE,
            'page_query_string' => TRUE,
            'query_string_segment' => $this->cur_page_segment,
            'base_url' => current_url() . "?" . (!is_null($this->input_get) ? http_build_query($this->input_get) : '')
        ));
        return (object) array(
                    'total' => $total_rows,
                    'per_page' => $per_page,
                    'cur_page' => $page,
                    'num_page' => ceil($total_rows / $per_page),
                    'data' => $query_data->result(),
                    'links' => $this->CI->pagination->create_links()
        );
    }

}

<?php
/**
 * SQLite 操作库
 * @author Roope <admin@cxsir.com>
 * @version 1.0.1
 */

class SQLite {

    /**
     * 构造函数
     * @param array $db 文件
     */
    public function __construct($db) {

        try {
            $this->db = new PDO('sqlite:'.$db);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }

    }

    /**
     * 析解函数，关闭PDO连接
     */
    public function __destruct() {
        $this->db = null;
    }

    /**
     * 执行SQL语句
     * @param  string $query SQL语句
     * @return mixed
     */
    public function query($query) {

        return $this->db->query($query);

    }

    /**
     * 解析键名
     * @param  array  $arr 键名数组
     * @return string
     */
    public function parseKey(array $arr) {

        $fields = null;

        foreach($arr as $idx => $key) {
                $fields .= $key . ',';
        }

        return rtrim($fields,',');
    }

    /**
     * 解析键值
     * @param  array  $arr 键值数组
     * @return string
     */
    public function parseValue(array $arr) {

        $value = null;

        foreach($arr as $idx => $val) {
            $value .=  '"' . $val . '",';
        }

        return rtrim($value,',');
    }

    /**
     * 解析WHERE
     * @param  array  $arr WHERE数组
     * @return string
     */
    public function parseWhere(array $where) {

        $condition = null;

        foreach($where as $op => $val) {
            $length = count($val);

            foreach ($val as $idx => $v) {
                $key = array_keys($v)[0];
                if($idx != $length-1) $condition .= "$key='$v[$key]' $op ";
                else $condition .= "$key='$v[$key]'";
            }
        }

        return $condition;
    }

    /**
     * 查询数据 用法 $db->select('trade',['field1','field2'],['AND' => [['id' => 1]]],10);
     * @param  string $table  表名
     * @param  array  $fields 需要查询的字段
     * @param  array $where  条件
     * @param  integer $limit  查询数量
     * @return mixed
     */
    public function select($table,array $fields,$where = null,$limit = null) {

        $fields = $this->parseValue($fields);

        if(empty($where)) {
            if (empty($limit)) $data = $this->db->query("SELECT $fields FROM $table");
            else $data = $this->db->query("SELECT $fields FROM $table LIMIT 0,$limit");
        } else {
            $where = $this->parseWhere($where);
            if (empty($limit)) $data = $this->db->query("SELECT $fields FROM $table WHERE $where");
            else $data = $this->db->query("SELECT $fields FROM $table WHERE $where LIMIT 0,$limit");
        }

        // if(!$data->fetchColumn()) return false;
        return $data->fetchAll();

    }

    /**
     * 插入数据 用法 $db->insert('trade',['field1','field2'],['Sam','1111']);
     * @param  string $table 表名
     * @param  array  $keys  键名
     * @param  array  $value 键值
     * @return mixed
     */
    public function insert($table,array $keys,array $value) {

        $key = $this->parseKey($keys);
        $val = $this->parseValue($value);

        return $this->db->exec("INSERT INTO $table($key) VALUES ($val)");

    }

    /**
     * 更新数据 用法 $db->update('trade',['name' => '张三','amount' => '10000.11'],['AND' => [['id' => '1']]]);
     * @param  string $table 表名
     * @param  array  $value 值
     * @param  array|null $where 条件
     * @return mixed
     */
    public function update($table,array $value,$where = null) {

        $values = null;

        foreach($value as $idx => $val) {
            $values .= "$idx='$val',";
        }
        $data = rtrim($values,',');
        if(!empty($where)) {
            $where = $this->parseWhere($where);
            return $this->db->exec("UPDATE $table SET $data WHERE $where");
        } else {
            return $this->db->exec("UPDATE $table SET $data");
        }
    }

    /**
     * 删除数据 用法 $db->delete('trade',['AND' => [['id' => 1]]]);
     * @param  string $table 表名
     * @param  array $where 条件
     * @return mixed
     */
    public function delete($table,$where) {
        $where = $this->parseWhere($where);
        return $db->exec("DELETE FROM $table WHERE $where");
    }
}

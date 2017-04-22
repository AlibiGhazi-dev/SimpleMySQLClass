<?php

/**
 * Created by PhpStorm.
 * User: Alibi
 * Date: 16/04/2017
 * Time: 12:14
 */



include('config.inc.php');

class MySQL
{
    private $query;
    private $queryString;
    protected $_mysqli;
    protected $_pageLimit = 3;
    protected $_result;


    function __construct()
    {
        $this->connect();
    }

    public function error()
    {
        return mysqli_error($this->_mysqli);
    }

    function __destruct()
    {
        mysqli_close($this->_mysqli);
    }

    public function connect()
    {
        $this->_mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        if ($this->_mysqli->connect_error) {
            throw new Exception('Connect Error ' . $this->_mysqli->connect_errno . ': ' . $this->_mysqli->connect_error, $this->_mysqli->connect_errno);
        }
    }

    function select($table)
    {
        $this->query['method'] = 'select';
        $this->query['table'] = $table;
        return $this;
    }

    function setColumns()
    {
        $args = func_get_args();
        $tmp = [];
        foreach ($args as $value) {
            array_push($tmp, $value);
        }
        $this->query['columns'] = $tmp;
        return $this;
    }

    function where()
    {
        $this->query['where']['equals'] = array();
        $this->query['where']['likes'] = array();
        $this->query['where']['greaterThan'] = array();
        return $this;
    }

    function equals($first, $second)
    {
        if (isset($this->query['where'])) {
            $this->query['where']['equals'][count($this->query['where']['equals']) + 1] = [$first, '=', $second];
        }
        return $this;
    }

    function likes($first, $second)
    {
        if (isset($this->query['where'])) {
            $this->query['where']['likes'][count($this->query['where']['likes']) + 1] = [$first, 'LIKE', '"' . $second . '"'];
        }
        return $this;
    }

    function greaterThan($first, $second)
    {
        if (isset($this->query['where'])) {
            $this->query['where']['greaterThan'][count($this->query['where']['greaterThan']) + 1] = [$first, '>', $second];
        }
        return $this;
    }

    function buildQuery()
    {
        $output = $this->query['method'] . ' ';
        if (empty($this->query['columns'])) {
            $columns = '*';
        } else {
            $columns = '';
            foreach ($this->query['columns'] as $key => $value) {
                if ($key !== count($this->query['columns']) - 1) {
                    $columns .= $this->query['table'] . '.' . $value . ', ';
                } else {
                    $columns .= $this->query['table'] . '.' . $value;
                }
            }
        }
        $where = '';
        if (isset($this->query['where'])) {
            $tmp = [];
            foreach ($this->query['where'] as $key => $value) {

                foreach ($value as $item) {
                    $ph = $item[0] . ' ' . $item[1] . ' ' . $item[2];
                    array_push($tmp, $ph);
                }

            }
            $where = ' where ';
            foreach ($tmp as $key => $value) {
                if ($key !== count($tmp) - 1) {
                    $where .= $value . ' AND ';
                } else {
                    $where .= $value;
                }
            }

        }
        $output .= $columns . ' from ' . $this->query['table'] . $where;
        $this->queryString = $output;
        return $this;
    }

    function get($format = null)
    {
        $this->buildQuery();
        $result = $this->_mysqli->query($this->queryString);
        $arr = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $arr[] = $row;
        }
        if (strtoupper($format) === "XML") {
            return $this->toXML($arr);
        }elseif (strtoupper($format) === "JSON"){
            return $this->toJSON($arr);
        }
        return $arr;
    }

    function toJSON($arr)
    {
         return json_encode($arr);
    }


    private function toXML($arr)
    {
        $str = '<root><' . $this->query['table'] . 's>';
        $piv = '';
        for ($i = 0; $i < count($arr); $i++) {
            $piv .= '<' . $this->query['table'] . '>';
            foreach ($arr[$i] as $key => $value) {
                $piv .= '<' . $key . '>' . $value . '</' . $key . '>';
            }
            $piv .= '</' . $this->query['table'] . '>';
        }
        $str .= $piv . '</' . $this->query['table'] . 's></root>';
        return $str;
    }
}



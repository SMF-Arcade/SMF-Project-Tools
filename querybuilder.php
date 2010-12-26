<?php

class ProjectQuery
{
	protected $type;
	protected $from;
	protected $joins;
	protected $selects;
	
	function __construct($type = 'SELECT', $table, $as = null)
	{
		$this->type = $type;
		$this->from = array($table, $as == null ? $table : $as);
	}

	function select($field, $as = null)
	{			
		$this->selects[] = array($field, $as);
		
		return $this;
	}
	
	function join($type = 'INNER', $table, $as)
	{
		if (isset($this->joins[$as]))
			return false;
		
		if ($type == null)
			$type == 'INNER';
			
		$this->joins[$as] = array($type, $table);
		
		return $this;
	}
	
	function getQuery()
	{
		if ($this->type == 'SELECT')
			return $this->__select_query();
	}
	
	protected function __select_query()
	{
		$query = '
		SELECT ';

		foreach ($this->selects as $select)
			$query .= $select[0] . (!empty($select[1]) ? ' AS ' . $select[1] : '') . ', ';
			
		$query = substr($query, 0, -2);
			
		$query .= '
		FROM ' . $this->from[0] . ' AS ' . $this->from[1];
		
		foreach ($this->joins as $name => $join)
			$query .= '
			' . $join[0] . ' JOIN ' . $join[1] . ' AS ' . $name;
		
		return $query;
	}
}

$a = new ProjectQuery('SELECT', '{db_prefix}issues', 'i');
var_dump($a->join('INNER', '{db_prefix}members', 'mem')
		 ->select('mem.a', 'member_name')
		 ->join('INNER', '{db_prefix}members', 'mem2')->getQuery());
?>
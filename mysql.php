<?php
class DB
{
	var $link_id = 0;
	var $rs = 0;
	var $error_no = 0;
	var $error = "";
	var $debug = true;
	var $paging_size = 25;
	//*******************************************************************************************
	function get_database()
	{
		return "#databasename#";
	}
	function get_user()
	{
		return "#databaseuser#";
	}
	function get_host()
	{
		return "localhost";
	}
	function get_password()
	{
		return "#databasepassword#";
	}
	//*******************************************************************************************
	function halt($msg)
	{
		if ( $this->debug )
		{
			echo("<B>Database error:</B> $msg<BR>\n");
			echo("<B>MySQL error</B>: $this->error_no ($this->error)<BR>\n");
			die("Process of this page has been halted.");
		}	
	}
	//*******************************************************************************************
	function last_insert_id()
	{
		if ( mysql_affected_rows($this->link_id)>0 )
			return intval(mysql_insert_id());
		else
			return 0;
	}
	
	//*******************************************************************************************
	function dbConnect($p_connect = false)
	{ 
		if($this->link_id == 0)
		{
			if ($p_connect)
			{
				$this->link_id = mysql_pconnect($this->get_host(), $this->get_user(), $this->get_password());
			}
			else
			{
				$this->link_id = mysql_connect($this->get_host(), $this->get_user(), $this->get_password());
			}
			
			if (!$this->link_id)
			{
				$this->halt("link_id =".$this->link_id.", mysql connection failed");
			}
		
			$SelectResult = mysql_select_db($this->get_database(), $this->link_id);
			if(!$SelectResult)
			{
				$this->error_no = mysql_errno($this->link_id);
				$this->error = mysql_error($this->link_id);
				$this->halt("cannot select database <I>".$this->Database."</I>");
			}
		}
	}
	
	//*******************************************************************************************
	function query($Query, $page_limit = 0)
	{
		$this->dbConnect();
		if ( $page_limit > 0 )
		{	
			$sp = ($page_limit-1) * $this->paging_size;
			//$lp = $page_limit * $this->paging_size;
			$lp = $this->paging_size;
			$Query .= " Limit $sp,$lp";
			//echo $Query;
		}
		//echo $Query;
		$this->rs = mysql_query($Query, $this->link_id);
		$this->error_no = mysql_errno();
		$this->error = mysql_error();
		if (!$this->rs && $this->error_no==1062) //for duplicate record error
		{
			$this->halt("<BR>Invalid SQL: ".$Query);
		}
		return $this->rs;
	}
	
	//*******************************************************************************************
	function executeNonQuery($query)
	{
		$this->dbConnect();
		mysql_query($query, $this->link_id);
		$this->error_no = mysql_errno();
		$this->error = mysql_error();
		if ($this->error_no == 1062) //for duplicate record error
		{
			$this->halt("<BR>Invalid SQL: ".$query);
		}
	}
	
	//*******************************************************************************************
	function rsset()
	{
		$this->record = mysql_fetch_array($this->rs);
		$this->error_no = mysql_errno();
		$this->error = mysql_error();
		if ( isset($this->record) )
		{
			if ( !is_array($this->record) )
			{
				mysql_free_result($this->rs);
				$this->rs = 0;
			}
		}
		return $this->record;
	}

	//*******************************************************************************************
	function total_rows()
	{
		if ( isset($this->rs) && is_resource($this->rs) )
			return mysql_num_rows($this->rs);
		else
			return 0;
	}
	
	//*******************************************************************************************  
	function affected_rows() //affected rows
	{
		return mysql_affected_rows($this->link_id);
	}
	  
	//*******************************************************************************************
	function free_results()
	{
		if($this->rs != 0) mysql_free_result($this->rs);
	}
	
	//*******************************************************************************************
	function db_close()
	{
		if($this->link_id != 0) mysql_close($this->link_id);
	}
	
	//*******************************************************************************************
	function query_pagger($total_records, $current_page, $link_str, $link_css = "")
	{
		if ( $total_records > 0 && $current_page > 0 )
		{
			//echo $total_records,"<BR>",$current_page;
			//if ( $no_rows <= $this->paging_size )
			$no_pages = ceil($total_records / $this->paging_size);
			//echo "<BR>",$no_pages;
			for($i = 1; $i <= $no_pages; $i++)
			{
				if ($i == $current_page)
					echo "<b>$i</b>";
				else
					echo "<a href='$link_str&page_no=$i' class='$link_css'>$i</a>";
	
				if ($total_records > $i*$this->paging_size ) echo "&nbsp;|&nbsp;";
			}
		}
	}

	//*******************************************************************************************
	function genrateCombo($sql, $name, $value, $displayText,$valueText, $title= NULL, $startText= NULL, $onblur= NULL, $onchange= NULL)
	{
		$cbo = "<select name='".$name."' id='".$name."' ";
		if( $onblur != NULL)
			$cbo .= " onblur=".$onblur;
		if($onchange != NULL)
			$cbo .= " onchange= ".$onchange;
			
		$cbo .= ">";

		
		if($startText != NULL)
			$cbo .= "<option value=0>".$startText."</option>";
			

		$query = $sql;
			
		$this->query($query);
		while($row = $this->rsset())
		{
			$cbo .= "<option value='".$row[$value]."' ";
			if($title != NULL)
				$cbo .= " title='".$row[$title]."'";
				
			if($valueText == $row[$value])
				$cbo .= " selected ";

			$cbo .= ">".$row[$displayText]."</option>";
		}
		
		$cbo .= "</select>";
		$this->db_close();
		return $cbo;		
	}
	function paginationStart($sql,$per_page,$currentPage)
	{
		//$sql is your sql query;
		//$per_page is the limist of pagination for one page
		$this->page=$currentPage;
		if (!isset($this->page)){
			$this->page = 1;
		}
		
		$this->prev_page = $this->page - 1;
		$this->next_page = $this->page + 1;
		
		//echo $sql;
		//exit;
	
		$this->query($sql);
		$this->page_start = ($per_page * $this->page) - $per_page;
		$this->num_rows = $this->total_rows();
		
		if ($this->num_rows <= $per_page){
			$this->num_pages = 1;
		}else if (($this->num_rows % $per_page) == 0){
			$this->num_pages = ($this->num_rows / $per_page);
		}else{
			$this->num_pages = ($this->num_rows / $per_page) + 1;
		}
		$this->num_pages = (int) $this->num_pages;
		
		if (($this->page > $this->num_pages) || ($this->page < 0)){
				   print "<br>if (($page > $num_pages) || ($page < 0)){";
			$msg=urlencode("You have specified an invalid page number.");
			header("Location: $PHP_SELF?errmsg=$msg");
			exit(0);	
		}
		
		$sql = $sql . " LIMIT $this->page_start, $per_page";
		
		$this->query($sql);
		
		//Pass these returning parameters to paginationFooter page for making footer of pagination
	}
	
	function paginationFooter($queryString,$targetPage,$total_Pages,$next_Page,$prev_Page, $start_Page)
	{
		//$queryString= Extra variables that you want to send with querystring. like this name=$name&type=1 etc
		//$targetPage = Page to be paginaged
		//$totalPages=Total number of pages;
		//$nextPage=Next page number;	
		//$prevPage=Previous page number;
		
		$this->num_pages = $total_Pages;
		$this->prev_page = $prev_Page;
		$this->next_page = $next_Page;
		$this->page = $start_Page;
		
		$output="";
		if ($this->prev_page)
		{
			if(strlen($queryString) > 0)
				$queryString = "-".$queryString;
		   $output .= "<a href=$targetPage&page_no=$this->prev_page".$queryString.">Previous</a>";
		}
		if ($this->num_pages>1)
		{
			if($this->page>5)
				$st_page=$this->page-5;
			else
				$st_page=1;		
		
			if($this->num_pages>10 && ($this->num_pages-$this->page)>5)
				$end_page = $this->page+5;
			else
				$end_page=$this->num_pages;		
		
			for($kk=$st_page;$kk<=$end_page;$kk++)
			{
				if($kk==$this->page)
					$output .= "&nbsp;$kk&nbsp;";
				else
				{
					if(strlen($queryString) > 0)
						$queryString = "-".$queryString;
					$output .= "&nbsp;<a href=$targetPage&page_no=$kk".$queryString.">$kk</a>&nbsp;";
				}
			}
		}
		if ($this->page != $this->num_pages)
		{
			if($this->num_pages!=1)
			{
				if(strlen($queryString) > 0)
					$queryString = "-".$queryString;
				$output .= "<a href=$targetPage&page_no=$this->next_page".$queryString.">Next </a>";
			}
		}
		return $output;
	}	
}
?>


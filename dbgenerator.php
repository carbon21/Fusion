<?php

function singularize($word)
{
	$singular = array (
	'/(quiz)zes$/i' => '\1',
	'/(matr)ices$/i' => '\1ix',
	'/(vert|ind)ices$/i' => '\1ex',
	'/^(ox)en/i' => '\1',
	'/(alias|status)es$/i' => '\1',
	'/([octop|vir])i$/i' => '\1us',
	'/(cris|ax|test)es$/i' => '\1is',
	'/(shoe)s$/i' => '\1',
	'/(o)es$/i' => '\1',
	'/(bus)es$/i' => '\1',
	'/([m|l])ice$/i' => '\1ouse',
	'/(x|ch|ss|sh)es$/i' => '\1',
	'/(m)ovies$/i' => '\1ovie',
	'/(s)eries$/i' => '\1eries',
	'/([^aeiouy]|qu)ies$/i' => '\1y',
	'/([lr])ves$/i' => '\1f',
	'/(tive)s$/i' => '\1',
	'/(hive)s$/i' => '\1',
	'/([^f])ves$/i' => '\1fe',
	'/(^analy)ses$/i' => '\1sis',
	'/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
	'/([ti])a$/i' => '\1um',
	'/(n)ews$/i' => '\1ews',
	'/s$/i' => '',
	);

	$uncountable = array('equipment', 'information', 'rice', 'money', 'species', 'series', 'fish', 'sheep');

	$irregular = array(
	'person' => 'people',
	'man' => 'men',
	'child' => 'children',
	'sex' => 'sexes',
	'move' => 'moves');

	$lowercased_word = strtolower($word);
	foreach ($uncountable as $_uncountable){
		if(substr($lowercased_word,(-1*strlen($_uncountable))) == $_uncountable){
			return $word;
		}
	}

	foreach ($irregular as $_plural=> $_singular){
		if (preg_match('/('.$_singular.')$/i', $word, $arr)) {
			return preg_replace('/('.$_singular.')$/i', substr($arr[0],0,1).substr($_plural,1), $word);
		}
	}

	foreach ($singular as $rule => $replacement) {
		if (preg_match($rule, $word)) {
			return preg_replace($rule, $replacement, $word);
		}
	}

	return $word;
}

if(isset($_GET['a']) && $_GET['a'] == 'go') {

	$mysqli = @new mysqli($_GET['server'], $_GET['user'], $_GET['password'], $_GET['database']);

	if ($mysqli->connect_error)
		die('Connect Error (' . $mysqli->connect_errno . ') '. $mysqli->connect_error);

	$path = 'database/'.$_GET['database'];
	$mpath = 'database/'.$_GET['database'].'/models';
		
	if(!is_dir('database'))
		mkdir('database');

	if(!is_dir($path))
		mkdir($path);
		
	if(!is_dir($mpath))
		mkdir($mpath);

	$mydir = opendir($mpath);
    while(false !== ($file = readdir($mydir))) {
        if($file != "." && $file != "..") {
            unlink($mpath.'/'.$file) or die("couldn't delete $dir$file<br />");
        }
    }
    closedir($mydir);
		
	$result = $mysqli->query('SHOW TABLES FROM '.$_GET['database']);

	if (!$result) {
		echo "DB Error, could not list tables\n";
		echo 'MySQL Error: ' . mysql_error();
	}

	while ($row = $result->fetch_row()) {
		echo "Creating Object: ".makenice($row[0])."<br />";
		
		$objName = singularize(makenice($row[0]));

		$objText = "<?php\r\n\r\n";
		$objText .= "namespace Models {\r\n";
		$objText .= "\tclass ".$objName." {\r\n";
		

		$result2 = $mysqli->query('SHOW COLUMNS FROM '.$row[0]);
		
		while ($row2 = $result2->fetch_row()) {
			$objText .= "\t\tpublic \$".makenice($row2[0]).";\r\n";
		}

		$objText .= "\r\n";
		
		$result3 = $mysqli->query('select `TABLE_NAME` from `information_schema`.`REFERENTIAL_CONSTRAINTS` where `REFERENCED_TABLE_NAME` = \''.$row[0].'\'');
		
		while ($row3 = $result3->fetch_row()) {
			$objText .= "\t\tpublic \$".makenice($row3[0])." = array();\r\n";
		}

		$objText .= "\r\n";
		
		$result3 = $mysqli->query('select `REFERENCED_TABLE_NAME` from `information_schema`.`REFERENTIAL_CONSTRAINTS` where `TABLE_NAME` = \''.$row[0].'\'');
		
		while ($row3 = $result3->fetch_row()) {
			$objText .= "\t\tpublic \$".singularize(makenice($row3[0])).";\r\n";
		}
		
		$objText .= "\t}\r\n\r\n";
		$objText .= "}\r\n?>";
		
		file_put_contents($mpath.'/'.strtolower($objName).'.php', $objText);









		$objText = "<?php\r\n\r\n";
		$objText .= "namespace Models {\r\n";
		
		$objText .= "\tclass ".$objName."MetaData extends \Core\Database\MetaData {\r\n";
		
		$objText .= "\t\tpublic function __construct() {\r\n";
		$objText .= "\t\t\t\$this->SetProperties(array(\r\n";

		$result4 = $mysqli->query('SHOW COLUMNS FROM '.$row[0]);
		
		while ($row4 = $result4->fetch_row()) {
			$objText .= "\t\t\t\t'".makenice($row4[0])."' => array(".(($row4[3] == 'PRI')?'\'pk\' => true':'')."),\r\n";
		}
		$objText = rtrim($objText, ",\r\n")."\r\n";
		
		$result3 = $mysqli->query('select `REFERENCED_TABLE_NAME` from `information_schema`.`REFERENTIAL_CONSTRAINTS` where `TABLE_NAME` = \''.$row[0].'\'');
		
		while ($row3 = $result3->fetch_row()) {
			$objText .= "\t\t\t\t,'".singularize(makenice($row3[0]))."' => array('type' => 'object', 'typeof' => '".singularize(makenice($row3[0]))."')\r\n";
		}
		
		$result3 = $mysqli->query('select `TABLE_NAME` from `information_schema`.`REFERENTIAL_CONSTRAINTS` where `REFERENCED_TABLE_NAME` = \''.$row[0].'\'');
		
		while ($row3 = $result3->fetch_row()) {
			$objText .= "\t\t\t\t,'".makenice($row3[0])."' => array('type' => 'array', 'typeof' => '".singularize(makenice($row3[0]))."')\r\n";
		}

		$objText .= "\t\t\t));\r\n";
		$objText .= "\t\t}\r\n";

		
		$objText .= "\t\tpublic function Create(\$prefix, \$row) {\r\n";
		$objText .= "\t\t\t\$x = new ".$objName."();\r\n";
		
		$result2 = $mysqli->query('SHOW COLUMNS FROM '.$row[0]);
		
		$pk = 'Id';
		while ($row2 = $result2->fetch_row()) {
			$objText .= "\t\t\t\$x->".makenice($row2[0])." = \$row[\$prefix.'".makenice($row2[0])."'];\r\n";
			
			if($row2[3] == 'PRI')
				$pk = $row2[0];
		}
		
		$objText .= "\t\t\treturn \$x;\r\n";
		$objText .= "\t\t}\r\n";

		$objText .= "\t\tpublic function Hash(\$obj) {\r\n";
		$objText .= "\t\t\treturn \$obj->".$pk.";\r\n";
		$objText .= "\t\t}\r\n";
		
		
		$objText .= "\t}\r\n";
		$objText .= "}\r\n?>";

		file_put_contents($mpath.'/'.strtolower($objName).'metadata.php', $objText);
	}


	$mysqli->close();
}

function makenice($string) {
	return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
}

?>



<form action="" method="get">
<input type="hidden" name="a" value="go" />

<label>Server:</label><br />
<input type="text" name="server" value="<?php echo isset($_GET['server']) ? $_GET['server'] : ''; ?>" /><br />
<br />
<label>User:</label><br />
<input type="text" name="user" value="<?php echo isset($_GET['user']) ? $_GET['user'] : ''; ?>" /><br />
<br />
<label>Password:</label><br />
<input type="text" name="password" value="<?php echo isset($_GET['password']) ? $_GET['password'] : ''; ?>" /><br />
<br />
<label>Database:</label><br />
<input type="text" name="database" value="<?php echo isset($_GET['database']) ? $_GET['database'] : ''; ?>" /><br />
<br />
<input type="submit" vlaue="generate" />

</form>
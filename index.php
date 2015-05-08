<?php
error_reporting(0); //turn on or off error reporting.

//CONFIGURE
backup_tables('localhost','username','password','database'); //database information
$path = '/path/to/files/'; //path to mysql folder.

//Time in which to save backups.
$days = 7; //days in which to save backups
$hours = 24; //hours in which to save backups
$seconds = 3600; //seconds in which to save backups

//DO NOT EDIT BELOW THIS LINE
function backup_tables($host,$user,$pass,$name,$tables = '*')
{

    $link = mysql_connect($host,$user,$pass);
    mysql_select_db($name,$link);

    //get all of the tables
    if($tables == '*')
    {
        $tables = array();
        $result = mysql_query('SHOW TABLES');
        while($row = mysql_fetch_row($result))
        {
            $tables[] = $row[0];
        }
    }
    else
    {
        $tables = is_array($tables) ? $tables : explode(',',$tables);
    }

    //cycle through
    foreach($tables as $table)
    {
        $result = mysql_query('SELECT * FROM '.$table);
        $num_fields = mysql_num_fields($result);

        $return = 'DROP TABLE '.$table.';';
        $row2 = mysql_fetch_row(mysql_query('SHOW CREATE TABLE '.$table));
        $return.= "\n\n".$row2[1].";\n\n";

        for ($i = 0; $i < $num_fields; $i++)
        {
            while($row = mysql_fetch_row($result))
            {
                $return.= 'INSERT INTO '.$table.' VALUES(';
                for($j=0; $j<$num_fields; $j++)
                {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = preg_replace('((\n))','((\\n))',$row[$j]);
                    if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
                    if ($j<($num_fields-1)) { $return.= ','; }
                }
                $return.= ");\n";
            }
        }
        $return.="\n\n\n";
    }

    //save file
    $handle = fopen('mysql/db-backup-'.time().'-'.(md5(implode(',',$tables))).'.sql','w+');
    fwrite($handle,$return);
    fclose($handle);
}

//file cleanup
if ($handle = opendir($path)) {

    while (false !== ($file = readdir($handle))) {
        $filelastmodified = filemtime($path . $file);
        //Permissions needed for some servers.
        chmod($path.$file, 0777);
        if((time() - $filelastmodified) > $days*$hours*$seconds)
        {
            unlink($path.$file);
        }

    }

    closedir($handle);
}
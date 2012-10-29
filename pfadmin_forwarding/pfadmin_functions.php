<?php
function removeempty2($str) {
  $tmp=array();
  $inp=explode(',',$str);
  foreach($inp as $key => $value) {
    if($value == "") {
      unset($inp[$key]);
    }
  }
  $tmp = array_values($inp);
//  $res = implode(',',$tmp);
  return implode(',',$tmp);
}

function packarray2($inp) {
  foreach($inp as $key => $value) {
     if($value == "") {
           unset($inp[$key]);
     }
  }
  return array_values($inp);

}

function removefromalias2($alias,$konto){

        $res = mysql_query("select address, goto from alias where address = '$alias'");
        if (mysql_num_rows($res)){
                $row =mysql_fetch_assoc($res);
                $aliasy = explode(',',$row["goto"]);
                $newalias = array();
                for ($i=0;$i<count($aliasy);$i++)
                        if (strcmp($aliasy[$i], $konto))
                                $newalias[]=$aliasy[$i];
                mysql_free_result($res);
                if (count($newalias)){
                        $newgoto = implode(',',array_unique($newalias));
                        mysql_query("update alias set goto='$newgoto' where address = '$alias'");
                }
                else
                        mysql_query("delete from alias where address = '$alias'");
                }
}


function addtoalias2($alias, $konto) {
        $res = mysql_query("select address, goto from alias where address = '$alias'");
        $aliasy = array();
        $alreadyincluded =0;
        if (mysql_num_rows($res)){
                $row =mysql_fetch_assoc($res);
                $alreadyincluded = (strpos($row["goto"],$konto));
                if (! $alreadyincluded)
                        $aliasy = explode(',',$row["goto"]);
        }
        mysql_free_result($res);
        if (!$alreadyincluded) {
                $aliasy[]=$konto;
                $newgoto =implode(',',array_unique($aliasy));
                mysql_query("insert into alias(address, goto) values ('$alias', '$newgoto') on duplicate key update goto='$newgoto'");
        }
}

?>
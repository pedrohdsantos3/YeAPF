<?php
/*
    skel/service/tasks.php
    YeAPF 0.8.58-96 built on 2017-07-04 16:28 (-3 DST)
    Copyright (C) 2004-2017 Esteban Daniel Dortta - dortta@yahoo.com
    2017-07-04 16:28:30 (-3 DST)
*/

  require_once "yeapf.php";

  /* you need to create a CROND entry calling this script as in the next example:
      http://example.com/tasks.php?s=yeapf&a=tick
  */
  if ($s=='yeapf') {
    switch($a) {
      case 'tick':
        require_once "yeapf_ticker.php";
        break;
    }
  }

?>
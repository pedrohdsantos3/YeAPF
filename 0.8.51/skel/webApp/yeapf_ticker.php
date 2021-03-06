<?php
/*
    skel/webApp/yeapf_ticker.php
    YeAPF 0.8.51-39 built on 2016-10-13 10:38 (-3 DST)
    Copyright (C) 2004-2016 Esteban Daniel Dortta - dortta@yahoo.com
    2016-06-02 14:26:09 (-3 DST)
*/

  require_once "yeapf.php";

  $ytasker=new YTaskManager();
  $taskId=$ytasker->getNextIdleTask();
  if ($taskId>0) {
    $taskContext=$ytasker->getTaskContext(true);
    if ($taskContext['s'].'.'.$taskContext['a']!='yeapf.tick') {
      try {
        /* if the task can be played, play it */
        if ($ytasker->playTask()) {
          set_time_limit(0);
          implementation($taskContext['s'], $taskContext['a'], 't');          
        }
      } catch (Exception $e) {

      }
      /* if the task will survive, enable it for the next pool */
      if ($ytasker->taskCanRun())
        $ytasker->enableTask();
    }
  }
?>
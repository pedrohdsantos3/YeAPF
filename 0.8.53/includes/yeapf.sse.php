<?php
  /*
    includes/yeapf.sse.php
    YeAPF 0.8.53-30 built on 2017-01-12 15:16 (-2 DST)
    Copyright (C) 2004-2017 Esteban Daniel Dortta - dortta@yahoo.com
    2017-01-12 14:16:52 (-2 DST)
   */

  class SSE
  {
    static $eventSequence=0;
    static $__KeepAliveInterval__=30;
    static $__LastPacketTS=0;
    static $queue_folder="";
    static $__needToFlush=true;

    /* indicates that the SSE can be used */
  	public function enabled($sse_session_id, $w, $u)
    {
      _dumpY(8,3,"SSE::enabled($sse_session_id)?");
      $ret=false;
      if (connection_status()==0) {
        $sessionFile=".sse/sessions/$sse_session_id.session";
        if (file_exists($sessionFile)) {
          $w = preg_replace('/[[:^print:]]/', '', $w);
          $u = preg_replace('/[[:^print:]]/', '', $u);

          $sessionInfo=file($sessionFile);
          $w1 = preg_replace('/[[:^print:]]/', '', $sessionInfo[0]);
          $u1 = preg_replace('/[[:^print:]]/', '', $sessionInfo[1]);

          if (($w1==$w) && ($u1==$u)) {
            if (is_dir(".sse/$w")) {
              $ret=is_dir(".sse/$w/$u");
              if ($ret) {
                self::$queue_folder=".sse/$w/$u";
              } else {
                _dumpY(8,3,"SSE::disabled - user directory not found");
              }
            } else {
              _dumpY(8,3,"SSE::disabled - workgroup directory not found");
            }
          } else {
            _dumpY(8,3,"SSE::disabled - session info differs from requested");
          }
        } else {
          _dumpY(8,3,"SSE::disabled - session file not found");
        }
      } else {
        _dumpY(8,3,"SSE::disabled - disconnection detected");
      }
      _dumpY(8,3,"SSE::enabled = ".intval($ret));
  		return $ret;
  	}

    /* flush the output to the client */
    private function __flush()
    {
      if (self::$__needToFlush) {
        @ob_end_flush();
        @flush();
        self::$__LastPacketTS = date('U');
        self::$__needToFlush=false;
      }
    }

    private function __echo()
    {
      $arg_list = func_get_args();
      foreach($arg_list as $arg) {
        self::$__needToFlush=true;
        _dump("SSE::__echo() $arg");
        echo $arg;
      }
    }

    /* send an event to the connected client */
    public function sendEvent($eventName, $eventData="")
    {
      _dumpY(8,3,"SSE::sendEvent('$eventName', '$eventData')");
      $evId = date('U').'.'.(self::$eventSequence++);
      self::__echo("event: $eventName\n");
      self::__echo("id: $evId\n");
      if (is_array($eventData))
        $eventData=json_encode($eventData);
      self::__echo("data: $eventData\n\n");
      self::__flush();
    }

    /* send a dummy packect when nothing has been sent in 30 seconds */
    public function keepAlive()
    {
      $t=date('U');
      if ( ($t-self::$__LastPacketTS) >= self::$__KeepAliveInterval__) {
        _dumpY(8,3,"SSE::keepAlive()");
        /* if nothing has been sent in the last n seconds, send a dummy packet */
        self::__echo(": ".$t."\n\n");
        self::__flush();
      }
    }

    public function processQueue($callback)
    {
      $queueFlag=self::$queue_folder."/ready.flag";

      if (file_exists($queueFlag)) {
        $u_target=basename(self::$queue_folder);
        $lockName=$u_target."-queue";

        if (lock($lockName,true)) {
          _dumpY(8,3,"SSE::processQueue()");
          $files=glob(self::$queue_folder."/*.*");
          array_multisort(
            array_map( 'filemtime', $files ),
            SORT_NUMERIC,
            SORT_ASC,
            $files
          );

          foreach ($files as $key => $value) {
            $ok=fnmatch("*.msg", basename($value));

            $f=fopen($value, "r");
            $eventName = trim(preg_replace('/[[:^print:]]/', '', fgets($f)));
            $eventData = preg_replace('/[[:^print:]]/', '', fgets($f));
            fclose($f);
            if ($eventName>'') {
              _dumpY(8,4,"SSE::processQueue() - $value - $eventName - $eventData");
              $callback($eventName, $eventData);
              unlink($value);
            }
          }
          unlink($queueFlag);
          unlock($lockName);
        }
      }
    }

    public function dettachUser($w, $u)
    {
      _dumpY(8,1,"SSE::dettachUser('$w', '$u')");
      $ndxFile=".sse/$u.ndx";
      if (file_exists($ndxFile)) {
        $ndx = file($ndxFile);
        $w_target       = preg_replace('/[[:^print:]]/', '', $ndx[0]);
        $msg_ndx        = intval(preg_replace('/[[:^print:]]/', '', $ndx[1]))+1;
        $sse_session_id = preg_replace('/[[:^print:]]/', '', $ndx[2]);
        $si             = md5($sse_session_id);
        @unlink(".sse/sessions/$sse_session_id.session");
        @unlink(".sse/sessions/$si.md5");
      }
    }

    /* grants that the user folder exists (this function si meant to be called at login time)
       generate sse_session_id */
    public function attachUser($w, $u)
    {
      $w = preg_replace('/[[:^print:]]/', '', $w);
      $u = preg_replace('/[[:^print:]]/', '', $u);
      /* dettach other session for this pair */
      self::dettachUser($w, $u);

      _dumpY(8,1,"SSE::attachUser('$w', '$u')");
      $ret=null;
      if ($w>'') {
        if ($u>'') {
          if (!is_dir(".sse/sessions"))
            mkdir(".sse/sessions", 0777, true);
          if (is_dir(".sse/sessions")) {
            if (!is_dir(".sse/$w/$u"))
              mkdir(".sse/$w/$u", 0777, true);
            if (is_dir(".sse/$w/$u")) {
              sleep(2);

              $sse_session_id = UUID::v4();
              $si             = md5($sse_session_id);

              file_put_contents(".sse/$w/$u/.user", "$u");
              file_put_contents(".sse/$u.ndx", "$w\n1000\n$sse_session_id\n");
              file_put_contents(".sse/sessions/$sse_session_id.session", "$w\n$u\n");
              file_put_contents(".sse/sessions/$si.md5", $sse_session_id);
              $ret=$sse_session_id;

              _dumpY(8,2,"SSE::user attached: $sse_session_id ($si)");
            }
          }
        }
      }
      return $ret;
    }


    public function getSessionId($si)
    {
      _dumpY(8,0, "getSessionId($si)");
      $ret=null;
      if (file_exists(".sse/sessions/$si.md5")) {
        $ret=file_get_contents(".sse/sessions/$si.md5");
        // unlink(".sse/sessions/$si.md5");
      }
      return $ret;
    }

    public function getSessionInfo($sse_session_id)
    {
      _dumpY(8,0, "getSessionInfo($sse_session_id)");
      $ret=array();
      if (file_exists(".sse/sessions/$sse_session_id.session")) {
        $data=file(".sse/sessions/$sse_session_id.session");
        $ret["w"]=$data[0];
        $ret["u"]=$data[1];
      }
      return $ret;
    }

    /* messages being sent from a client (rest or query) to another client (sse) */
    function __enqueueMessage($u_target, $message, $data='')
    {
      $messageFile='';
      $lockName=$u_target."-queue";
      if (lock($lockName)) {
        _dumpY(8,3,"SSE::__enqueueMessage('$u_target', '$message', '$data')");
        $ndxFile=".sse/$u_target.ndx";
        if (file_exists($ndxFile)) {
          $ndx = file($ndxFile);
          $w_target       = preg_replace('/[[:^print:]]/', '', $ndx[0]);
          $msg_ndx        = intval(preg_replace('/[[:^print:]]/', '', $ndx[1]))+1;
          $sse_session_id = preg_replace('/[[:^print:]]/', '', $ndx[2]);
          $usr_folder = ".sse/$w_target/$u_target";
          if (is_dir($usr_folder)) {
            file_put_contents("$ndxFile", "$w_target\n$msg_ndx\n$sse_session_id\n".date("U"));
            $messageFileI = "$usr_folder/$msg_ndx.new";
            $messageFileF = "$usr_folder/$msg_ndx.msg";
            $f=fopen($messageFileI, "wt");
            fputs($f, "$message\n");
            fputs($f, "$data\n");
            fclose($f);

            rename($messageFileI, $messageFileF);

            touch("$usr_folder/ready.flag");
          }
        }
        unlock($lockName);
      }
      return $messageFile;
    }

    /* send a message and wait to it be processed by the target
       returns true if the message was delivered
       returns false if the queue does not exists */
    public function sendMessage($u_target, $message, $data='')
    {
      _dumpY(8,2,"SSE::sendMessage('$u_target', '$message', '$data')");
      $ret=false;
      $messageFile=self::__enqueueMessage($u_target, $message, $data);
      if ($messageFile>'') {
        $ret=true;
        while (file_exists($messageFile)) {
          usleep(500000);
        }
      }
      return $ret;
    }

    /* post a message and return immediatly */
    public function postMessage($u_target, $message, $data='')
    {
      _dumpY(8,2,"SSE::postMessage('$u_target', '$message', '$data')");
      $ret=false;
      $messageFile=self::__enqueueMessage($u_target, $message, $data);
      if ($messageFile>'') {
        $ret=true;
      }
      return $ret;
    }

    /* post a message to all the workgroup */
    public function broadcastMessage($message, $data='', $w_target='*')
    {
      _dumpY(8,2,"SSE::broadcastMessage('$message', '$data', '$w_target')");
      $w_target = preg_replace('/[[:^print:]]/', '', $w_target);
      if ($w_target=='*') {
        $dh=opendir(".sse");
        if ($dh) {
          while (($f = readdir($dh)) !== false) {
            $fileinfo=pathinfo($f);
            $ok=fnmatch("*.ndx", $fileinfo['basename']);
            if ($ok) {
              self::postMessage($fileinfo['filename'], $message, $data);
            }
          }
          closedir($dh);
        }
      } else {
        if (is_dir(".sse/$w_target")) {
          $dh=opendir(".sse/$w_target");
          if ($dh) {
            while (($f = readdir($dh)) !== false ) {
              $u_target = basename($f);
              if (!is_dir($f)) {
                self::postMessage($u_target, $message, $data);
              }
            }
            closedir($dh);
          }
        }
      }
    }
  }


  function qsse($a)
  {
    global $userContext, $sysDate, $u,
           $fieldValue, $fieldName,
           $userMsg, $xq_start;

    $useColNames = true;
    $countLimit=20;
    $ret='';

    extract(xq_extractValuesFromQuery());
    $xq_start=isset($xq_start)?intval($xq_start):0;

    switch($a)
    {
      case 'attachUser':
        $sse_session_id  = SSE::attachUser($w, $u);
        $ret = array(
              'ok'=>$sse_session_id>'',
              'sse_session_id' => $sse_session_id
            );
        break;

      case 'peekMessage':
        $ret=array();
        $sessionInfo = SSE::getSessionInfo($sse_session_id);
        extract($sessionInfo);
        if (SSE::enabled($sse_session_id, $w, $u)) {
          $sse_dispatch = function($eventName, $eventData) {
            $ret[]=array(  'event' => $eventName,
                           'data'  => $eventData   );
          };
          SSE::processQueue($sse_dispatch);
        }
        break;
    }

    xq_produceReturnLines($ret, $useColNames, $countLimit);

  }


?>
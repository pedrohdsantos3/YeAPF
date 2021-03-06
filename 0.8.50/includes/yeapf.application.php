<?php
/*
    includes/yeapf.application.php
    YeAPF 0.8.50-34 built on 2016-09-17 17:59 (-3 DST)
    Copyright (C) 2004-2016 Esteban Daniel Dortta - dortta@yahoo.com
    2016-09-17 17:58:39 (-3 DST)
*/
  _recordWastedTime("Gotcha! ".$dbgErrorCount++);

  $yHtml_Header=false;
  $yHtml_CSS=false;
  $yHtml_Body=false;
  $yHtml_Footer=false;

  function closeCurrentWindow()
  {
    echo "<script>window.close();</script>";
  }

  function redirectOpener($s, $a, $url='')
  {
    global $u;

    if ($url>'') {
      if ((substr($url,0,1)=='?') || (substr($url,0,1)=='&'))
        $url=substr($url,1);
      $urlItems=explode('&',$url);
      $url='';
      foreach($urlItems as $ui) {
        $ui=explode('=', $ui);
        $k=$ui[0];
        $v=$ui[1];
        if (!((strtolower($k)=='s') || (strtolower($k)=='a') || (strtolower($k)=='u'))) {
          if ($url>'')
            $url.='&';
          $url.="$k=$v";
        }
      }
      _dumpY(16,0,"Redirecting opener to '$url'");
    }
    echo "<script>window.opener.document.location = '?u=$u&s=$s&a=$a&$url';</script>";
  }

  function _createHTMLHeader_($cssHeaderText)
  {
    global $yHtml_Header, $appCharset, $cfgAppLang, $appTitle;

    if (!$yHtml_Header) {
      $yHtml_Header=true;

      echo "<!DOCTYPE html>\n";
      if ($cfgAppLang>'')
        echo "<html lang='$cfgAppLang'>\n";
      echo "<head>\n";
      echo "<meta http-equiv='X-UA-Compatible' content='IE-edge,chrome=1' />\n";
      if ($appCharset>'')
        echo "<meta charset='$appCharset'>\n";
      else {
        $serverCharset = setlocale(LC_CTYPE, 0);
        if (strpos($serverCharset, '.')>0)
          $serverCharset = substr($serverCharset, strpos($serverCharset, '.')+1);
        echo "<meta charset='$serverCharset'>\n";
      }
      echo "<title>$appTitle</title>\n";
      echo "<meta http-equiv='X-UA-Compatible' content='IE=EmulateIE8' />\n";
      echo $cssHeaderText;
      /*
      echo "\n<!--[if lt IE 9]>\n\t<script src='http://html5shiv.googlecode.com/svn/trunk/html5.js'></script>\n<![endif]-->\n";
      //echo "<!-- $appName.$s.$a.$aBody (WoH:$withoutHeader WoB:$withoutBody)-->\n";
      echo "<script src=".bestName("yloader.js",1)."></script>\n";
      */
      echo "</head>\n";
    }
  }

  /*
   * Load CSS if it is not an CLI application
   * Create HTML header
   */
  function initOutput()
  {
    global $appName, $a, $s, $aBody, $withoutBody, $withoutHeader, $isTablet;

    $cssHeaderText='';

    // echo "<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>\n";

    if ($isTablet)
      $device='mobile';
    else
      $device='desktop';
    _dumpY(16,1,"device: $device");

    // if there is a pre-configure css file, we try to load it
    // cfgUniversalCSS is loaded first and ever
    // cfgDesktopCSS ou cfgMobileCSS are loaded as the context indicates it
    $cssList=array();

    if (isset($GLOBALS["cfgUniversalCSS"]))
      if (file_exists($GLOBALS['cfgUniversalCSS']))
        array_push($cssList, $GLOBALS['cfgUniversalCSS']);

    $cfgVar="cfg".ucfirst($device)."CSS";
    if (isset($GLOBALS[$cfgVar]))
      if (file_exists($GLOBALS[$cfgVar]))
        array_push($cssList, $GLOBALS[$cfgVar]);

    $cssList=join(',', $cssList);

    if ($cssList=='') {
      $cwdName = basename(getcwd());
      $appBaseName=substr($cwdName,0,strpos($cwdName.'-','-'));
      /*
       * sequencia original de busca do CSS
      $cssNames = array("default", "default-$device",
                        "../$appName", "../$appName-$device",
                        "$appName/$appName", "$appName/$appName-$device",
                        "includes/default", "includes/default-$device",
                        "includes/$appName", "includes/$appName-$device",
                        "$appName", "$appName-$device",
                        "$appBaseName", "$appBaseName-$device",
                        "$cwdName", "$cwdName-$device");
      */
      $cssBaseNames = array ("default", "$appBaseName", "$appName", "$cwdName");
      _dumpY(16,0,$_SERVER['HTTP_USER_AGENT']);
      foreach($cssBaseNames as $cssBaseName) {
        $cssNames = array("$cssBaseName", "$cssBaseName-$device",
                          "css/$cssBaseName", "css/$cssBaseName-$device",
                          "$appName/$cssBaseName", "$appName/$cssBaseName-$device",
                          "includes/$cssBaseName", "includes/$cssBaseName-$device",
                          "../$cssBaseName", "../$cssBaseName-$device");
        foreach($cssNames as $cssName) {
          if (file_exists("$cssName.min.css"))
            $cssName.='.min.css';
          else
            $cssName.='.css';
          _dumpY(16,4,"is an $cssName ?");
          if ( (file_exists($cssName)) &&
               (strpos($cssList,$cssName)===FALSE) )  {
            if (file_exists('version.inf'))
              $version='v='.join('',file('version.inf'));
            else
              $version=md5(date('U'));
            $cssHeaderText.="\n<link href='$cssName?$version' rel='stylesheet' type='text/css'>";
            if ($cssList>'')
              $cssList.=', ';
            $cssList.=$cssName;
          }
        }
      }
    }
    _dumpY(16,1,"CSS: ($cssList) (Tablet?: $isTablet)");
    if ($cssList=='')
      _dumpY(16,0,"Without CSS");

    _createHTMLHeader_($cssHeaderText);
    echo "\n<!-- START OF OUTPUT -->\n";
  }

  function finishOutput()
  {
    global $s, $a, $u;
    echo "\n<!-- END OF OUTPUT FOR '$s.$a'-->\n";
    _dumpY(16,1,"End Output");
  }

  $__messagesHandler = array();


  // this functions transform comma separated parameters and value
  // in an associative array
  function getQueryParameters($asGlobals=false)
  {
    _die("OBSOLETO, utilize 'xq_extractValuesFromQuery()' em lugar de 'getQueryParameters()'");
    /*
    $fieldName=unparentesis($GLOBALS['fieldName']);
    $fieldValue=unparentesis($GLOBALS['fieldValue']);
    $ret=array();
    while ($fieldName>'') {
      $aFieldName=getNextValue($fieldName,',');
      $aFieldValue=getNextValue($fieldValue,',');
      $ret[$aFieldName]=$aFieldValue;
      if ($asGlobals)
        $GLOBALS[$aFieldName]=$aFieldValue;
    }
    return $ret;
    */
  }

  function xq_addToFieldList(&$fieldList, $domainTable, $fieldToBeAdd)
  {
    $fieldToBeAdd = explode(';',$fieldToBeAdd);
    foreach($fieldToBeAdd as $fieldName) {
      if (!in_array($fieldName, $fieldList))
        if (db_fieldExists($domainTable, $fieldName))
          $fieldList[]=$fieldName;
    }
  }

  function xq_getFieldList($domainTable='', $exceptionList='', $prefix='', $posfix='')
  {
    $exceptionList = explode(';',$exceptionList);
    $fieldName = unparentesis($GLOBALS['fieldName']);
    $fieldList = explode(",",$fieldName);

    $ret=array();
    foreach($fieldList as $fieldName) {
      $originalFieldName = $fieldName;

      $fieldNameCanBeUsed = ($domainTable=='');

      if (($prefix>'') && (substr($fieldName,0,strlen($prefix))==$prefix)) {
        $fieldName  = substr($fieldName, strlen($prefix));
        if (!$fieldNameCanBeUsed) {
          $fieldNameCanBeUsed = db_fieldExists($domainTable, $fieldName);
          if (!$fieldNameCanBeUsed)
            $fieldName = lcfirst($fieldName);
        }
      }

      if (($posfix>'') && (substr($fieldName,strlen($fieldName)-strlen($posfix))==$posfix)) {
        $fieldName = substr($fieldName, 0, strlen($fieldName)-strlen($posfix));
      }

      if (!$fieldNameCanBeUsed)
        $fieldNameCanBeUsed = db_fieldExists($domainTable, $fieldName);

      if ( $fieldNameCanBeUsed ) {
        if (!in_array($fieldName, $exceptionList))
          $ret[$fieldName] = $GLOBALS[$originalFieldName];
      }
    }

    return $ret;
  }

  function xq_extractValue(&$ret, $aFieldName, $aFieldValue, $asGlobals, $xq_prefix, $xq_postfix, $xq_only_composed_names=false)
  {
    $aFieldValue=urldecode($aFieldValue);
    $reserverWords = array('u', 's', 'a', 'fieldName', 'fieldValue');
    if (!in_array(strtolower($aFieldName), $reserverWords)) {
      _dumpY(16,0,"A: $aFieldName");
      if ((strtolower($aFieldValue)=='null') || (strtolower($aFieldValue)=='undefined'))
        $aFieldValue=null;
      $aFieldValue = str_replace("\!\!", '&', $aFieldValue);
      $canUse=!$xq_only_composed_names;

      /* discard prefix */
      if ($xq_prefix>'') {
        if (substr($aFieldName,0,strlen($xq_prefix))==$xq_prefix) {
          $canUse=true;
          $aFieldName=substr($aFieldName,strlen($xq_prefix));
        }
      }
      /* discard postfix */
      if ($xq_postfix>'') {
        if (substr($aFieldName, -strlen($xq_postfix))==$xq_postfix) {
          $canUse=true;
          $aFieldName=substr($aFieldName, 0, strlen($aFieldName)-strlen($xq_postfix));
        }
      }
      if (($canUse) && ($aFieldName>'')) {
        _dumpY(16,0,"B: $aFieldName");
        $ret[$aFieldName]=$aFieldValue;
        if ($asGlobals)
          $GLOBALS[$aFieldName]=$aFieldValue;
      }
    }
  }

  function xq_varValue($arrayOfValues, $varName)
  {
    $ret=null;
    if (isset($arrayOfValues[$varName]))
      $ret=$arrayOfValues[$varName];
    else if (isset($GLOBALS[$varName]))
      $ret=$GLOBALS[$varName];
    return $ret;
  }

  function xq_extractValuesFromQuery($asGlobals=false, $xq_prefix='', $xq_postfix='', $xq_only_composed_names=false)
  {
    $ret=array();


    if (isset($_REQUEST)) {
      foreach($_REQUEST as $k=>$v)
        xq_extractValue($ret, $k, $v, $asGlobals, $xq_prefix, $xq_postfix, $xq_only_composed_names);
    }
    
    $fieldName  = unparentesis(xq_varValue($ret, 'fieldName'));
    $fieldValue = unparentesis(xq_varValue($ret, 'fieldValue'));
    
    while ($fieldName>'') {      
      $aFieldName=unquote(getNextValue($fieldName,','));
      $aFieldValue=unquote(getNextValue($fieldValue,','));
      xq_extractValue($ret, $aFieldName, $aFieldValue, $asGlobals, $xq_prefix, $xq_postfix, $xq_only_composed_names);
    }
    // die('\n\n');
    return $ret;
  }

  function xq_printXML(&$output, $keyName, $keyValue)
  {
    if (is_array($keyValue)) {
      $output.="<$keyName>";
      foreach($keyValue as $k => $v)
        xq_printXML($output, $k, $v);
      $output.="</$keyName>";
    } else {
      if (strpos($keyName,'/') > 0 ) {
        $keyList=explode('/',$keyName);
        $k0=$keyList[0];
        $openKey='';
        $closeKey='';
        for($i=0;$i<count($keyList);$i++) {
          $k=$keyList[$i];
          if (is_numeric($k)) {
            $k=$k0.$k."_";
          }
          $openKey.="<$k>";
          $closeKey="</$k>".$closeKey;
        }
        $output.="$openKey$keyValue$closeKey";
      } else {
        if (is_numeric($keyName))
          $keyName="n$keyName";
        $output.="<$keyName>$keyValue</$keyName>\n";
      }
    }
    return $output;
  }

  global $_xq_context_;
  if (!isset($_xq_context_))
    $_xq_context_=array();

  function xq_context($aIndex, $aValue, $replaceIfExists=true)
  {
    global $_xq_context_;

    if (trim($aIndex)>'') {
      if (strpos($aIndex,'/')>0) {
        $keyList = explode('/', $aIndex);
        $k0=$keyList[0];
        if (!isset($_xq_context_[$k0]))
          $_xq_context_[$k0] = array();
        $aux=&$_xq_context_[$k0];
        for($i=1; $i<count($keyList)-1; $i++) {
          $k=$keyList[$i];
          if (!isset($aux[$k]));
            $aux[$k]=array();
          $aux=$aux[$k];
        }
        $itemKey=$keyList[count($keyList)-1];
        if (is_numeric($itemKey))
          $itemKey="$k0$itemKey";
        $aux[$itemKey]=$aValue;
      } else {
        if ((!is_array($aValue)) && (trim($aValue)=='')) {
          if (isset($_xq_context_[$aIndex]))
            unset($_xq_context_[$aIndex]);
        } else {
          if (($replaceIfExists) || (!isset($_xq_context_[$aIndex])))
            $_xq_context_[$aIndex]=$aValue;
        }
      }
    }

    return $_xq_context_;
  }

  function xq_produceContext($callBackFunction, $xmlRowsData, $cRegs, $userMsg=null, $firstRow=null, $requestedRows=null, $sqlID='',$progressBarID='', $navigatorID='',$formFile='')
  {
    global $formID, $targetTableID, $_xq_context_, $lastAction, $lastError, $_requiredFields, $xq_start, $xq_requestedRows;

    if ($firstRow == null)
      $firstRow = intval($xq_start);
    if ($requestedRows == null)
      $requestedRows = intval($xq_requestedRows);

    xq_context('navScript', $formFile, false);
    xq_context('formID', $formID, false);
    xq_context('targetTableID', $targetTableID, false);
    xq_context('navigatorID', $navigatorID, false);
    xq_context('progressBarID', $progressBarID, false);
    xq_context('sqlID', $sqlID, false);
    xq_context('requestedRows', $requestedRows, false);
    xq_context('firstRow', $firstRow, false);
    if ($userMsg!=null)
      xq_context('userMsg', $userMsg, false);
    xq_context('lastAction', $lastAction, false);
    xq_context('lastError', explode("\n",$lastError), false);
    xq_context('rowCount', intval($cRegs), false);
    xq_context('requiredFields', $_requiredFields, false);

    while (strpos($userMsg,"\n ")>0)
      $userMsg=str_replace("\n ", "\n", $userMsg);

    $xmlData ="  <callBackFunction>$callBackFunction</callBackFunction>\n";
    $xmlData.="  <dataContext>\n";
    foreach($_xq_context_ as $k => $v) {
      xq_printXML($xmlData, $k, $v);
    }
    $xmlData.="  </dataContext>\n";
    $xmlData.="  <data>\n$xmlRowsData</data>\n";
    return $xmlData;
  }

  /*
   * xq_produceReturnLinesFromArray
   * xq_produceReturnLinesFromSQL
   *
   * as duas geram linhas parciais de xml simples para usar com QUERY.PHP
   *
   * 26/ago/10 - caso precise calcular uma coluna a partir de outra,
   *             indique calc_nomeFuncao como nome do campo
   *             e implemente CALC_NOMEFUNCAO() nas suas rotinas
   * 29/out/10 - foi acrescentada xq_produceReturnLinesFromArray para gerar resultados
   *             a partir de um vetor associativo.  � usada por xq_produceReturnLinesFromSQL
   * 05/jul/11 - foi acrescentada xq_calculatedField para atender os campos CALC_NOMEFUNCAO()
   *             desde outros comandos db_*.  Veja por exemplo db_fetch_array
   */
  $uncoveredFunctions=Array();

  function xq_calculatedField(&$d, &$k, &$v)
  {
    global $uncoveredFunctions;

    $knum=intval(is_numeric($k));
    if (!$knum) {
      $funcName=strtoupper($k);
      if (substr($funcName,0,5)=='CALC_') {
        if (function_exists($k)) {
          $v=maskHTML($k($d));
        } else {
          if (! in_array($funcName, $uncoveredFunctions)) {
            array_push($uncoveredFunctions, $funcName);
            _dumpY(16,0,"ERROR: function '$funcName()' does not exists in context");
          }
        }
      }
    }
    return $v;
  }

  function xq_produceReturnLinesFromInnerArray($d, $colNames=false, $nonEmptyField='', $innerKeySeed='', $xq_prefix='', $xq_postfix='')
  {
    $ret='';
    foreach($d as $k => $v) {
      if ("$k"!='__COUNT__') {
        if (is_numeric($k)) {
          $keyName = $innerKeyNdx.'_'.$k;
        } else
          $keyName = trim($k);
        $keyName="$xq_prefix$keyName$xq_postfix";
        if (is_array($v))
          $v=xq_produceReturnLinesFromInnerArray($v, $colNames, $nonEmptyField, $keyName, $xq_prefix, $xq_postfix);
        $ret.="<$keyName>$v</$keyName>";
      }
    }
    return $ret;
  }

  function xq_produceReturnLinesFromArray($d, &$cRegs, $colNames=false, $nonEmptyField='', $xq_prefix='', $xq_postfix='')
  {
    global $xq_return_array, $_ydb_ready;

    $auxRow='';
    $col=0;

    $mandatoryFieldFilled=($nonEmptyField=='');

    $allAreNumricKeys=true;
    foreach($d as $k=>$v)
      if (!is_numeric($k))
        $allAreNumricKeys=false;

    if ($allAreNumricKeys) {
      $cRegs=intval($cRegs);

      foreach($d as $k=>$v) {
        if (is_array($v)) {
          $v=xq_produceReturnLinesFromInnerArray($v, $colNames, $nonEmptyField, '_'.$k, $xq_prefix, $xq_postfix);
        } else
          $v=maskHTML(trim($v));

        $xmlRow.="  <row rowid='$cRegs'>\n";
        $xmlRow.="    <rowid>$cRegs</rowid>\n";
        $xmlRow.="    <data>$v</data>";
        $xmlRow.="  </row>\n";

        $cRegs++;

      }

    } else {

      $colNames=intval($colNames);
      $CIKeys=array();

      foreach($d as $k=>$v) {
        if ("$k"!="__COUNT__") {
          $CIK=strtolower($k);
          if ((  ($_ydb_ready & _DB_CONNECTED_)==0) ||
                 (db_connectionTypeIs(_MYSQL_)) ||
              (!in_array($CIK, $CIKeys))) {
            $CIKeys[]=$CIK;

            $knum = is_numeric($k);
            if (is_array($v)) {
              $v=xq_produceReturnLinesFromInnerArray($v, $colNames, $nonEmptyField, '_'.$k, $xq_prefix, $xq_postfix);
            } else
              $v=maskHTML(trim($v));
            $canAdd=false;

            // $v=xq_calculatedField($d, $k, $v);

            _dumpY(16,3,"$k => $v");

            $fieldAttrib='';
            if ($colNames)
              $canAdd=!$knum;
            else {
              $canAdd=true;
              if ($canAdd) {
                $k="data";
                $col++;
                $fieldAttrib=" col='$col'";
              }
            }

            $k="$xq_prefix$k$xq_postfix";


            if ($canAdd) {
              $auxRow.="    <$k$fieldAttrib>$v</$k>\n";
              if ($k==$nonEmptyField) {
                $mandatoryFieldFilled=(trim($v)>'');
              }
            }
          }
        }
      }

      if ($mandatoryFieldFilled) {

        $cRegs=intval($cRegs);

        $xmlRow ="  <row rowid='$cRegs'>\n";
        $xmlRow.="    <rowid>$cRegs</rowid>\n";
        $xmlRow.="$auxRow";
        $xmlRow.="  </row>\n";

        $cRegs++;
      } else {
        $xmlRow='';
      }
    }

    return $xmlRow;
  }

  /* xq_produceReturnLinesFromSQL
   *    recebe um comando SQL
   *    devolve um xml parcial contendo as colunas indicadas no XML
   */

  function xq_produceReturnLinesFromSQL($sql, &$cRegs, $colNames=false, $maxRecordCount=-1, $nonEmptyField='', $xq_prefix='', $xq_postfix='')
  {
    global $uncoveredFunctions, $userMsg;

    $xmlRows='';
    $sql=html_entity_decode(unquote($sql));

    $q=db_query($sql);
    while ($d=db_fetch_array($q,false)) {
      if (!$colNames)
        $d=array_unique($d);
      $xmlRows.=xq_produceReturnLinesFromArray($d, $cRegs, $colNames, $nonEmptyField, $xq_prefix, $xq_postfix);
      if (($maxRecordCount>0) && ($cRegs>=$maxRecordCount)) {
        $userMsg="Limite de busca atingido.  Seja mais especifico";
        _dump($userMsg);
        break;
      }
    }


    return $xmlRows;
  }

  function xq_produceReturnLines($returnSet, $xq_usingColNames, $xq_countLimit, $xq_prefix='', $xq_postfix='')
  {
    global $xq_return, $xq_regCount, $xq_requestedRows;
    $xq_requestedRows = $xq_countLimit;

    if (is_array($returnSet)) {
      $isArrayOfArray=false;
      foreach($returnSet as $k=>$v)
        if (is_array($v))
          $isArrayOfArray=true;
      if ($isArrayOfArray) {
        $xq_return='';
        foreach($returnSet as $k=>$v) {
          $xq_return.= xq_produceReturnLinesFromArray($v, $xq_regCount, $xq_usingColNames, '', $xq_prefix, $xq_postfix);
        }
      } else
        $xq_return = xq_produceReturnLinesFromArray($returnSet, $xq_regCount, $xq_usingColNames, '', $xq_prefix, $xq_postfix);
    } else {
      if ($returnSet>'')
        $xq_return=xq_produceReturnLinesFromSQL($returnSet, $xq_regCount, $xq_usingColNames, $xq_countLimit, '', $xq_prefix, $xq_postfix);
    }
    return $xq_return;
  }

  function jr_produceReturnLines($returnSet) {
    if (is_string($returnSet)) {
      $auxSet=$returnSet;
      $auxSet=strtolower(getNextValue($auxSet,' '));
      if (($auxSet=="select") || ($auxSet=="insert") || ($auxSet=="delete") || ($auxSet=="update") || ($auxSet=="replace")) {
        
        $auxSet=db_queryAndFillArray($returnSet);
        $returnSet=array();

        foreach($auxSet as $d) {
          $auxLine=array();
          foreach($d as $k=>$v) {
            $canUse=true;
            if (db_connectionTypeIs(_FIREBIRD_))
              $canUse=(strtoupper($k)==$k);
            if ($canUse)
              if ($k!="__COUNT__")
                $auxLine[$k]=$v;
          }
          $returnSet[count($returnSet)] = $auxLine;
        }
      }
    }
    return json_encode($returnSet);
  }


  /* functions to verify form content */
  function strFilled($str)
  {
    return strlen(trim($str))>0;
  }

  function validEmail($email)
  {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
  }

  global $_xq_formErrorSequence_;
  $_xq_formErrorSequence_=0;

  function setFieldError($msg, $varName='')
  {
    global $_xq_formErrorSequence_;
    _requiredField($varName);
    $_xq_formErrorSequence_++;
    _recordError("$_xq_formErrorSequence_) $msg");
    xq_context("formError/$varName", $msg);
  }

  function verifyFormValue($varName, $func, $msg, $setAsFormError=true)
  {
    $ret=true;
    $functions=explode(';',$func);
    foreach($functions as $func) {
      if ($ret) {
        if (function_exists($func)) {
          if ($func($GLOBALS[$varName])==false) {
            if ($setAsFormError)
              setFieldError($msg, $varName);
            else {
              _requiredField($varName);
              _recordError($msg,0);
            }
            $ret=false;
          }
        } else
          _die("'$func' was not found as global function");
      }
    }
    return $ret;
  }


  /*
   * Functions to be used with Javascript Inter User Messages in
   * the context of YeAPF applications
   *
   */

  function qy_msgProc($aSourceUserId, $aMessage, $aWParam, $aLParam)
  {
    global $sysTimeStamp, $__messagesHandler;

    $ret=array();

    if ($aMessage=='') {
      $ret['sourceUserId']=$aSourceUserId;
      $ret['message']='systemTick';
      $ret['wParam']=$sysTimeStamp;
      $ret['lParam']=0;
    } else {
      $ret['sourceUserId']=$aSourceUserId;
      $ret['message']=$aMessage;
      $ret['wParam']=$aWParam;
      $ret['lParam']=$aLParam;

      foreach($__messagesHandler as $mh)
        if (function_exists($mh))
          $ret=$mh($aSourceUserId, $aMessage, $aWParam, $aLParam);
    }

    return $ret;
  }

  function qy_msg($a)
  {
    global $sysTimeStamp,
           $userContext,
           $u, $formID, $messagePeekerInterval,
           $xq_return, $xq_regCount,
           $targetUser, $message, $wParam, $lParam, $broadcastCondition;

    if (!is_object($userContext)) {
      $aux=debug_backtrace();
      foreach($aux as $k=>$v) {
        foreach($v as $k1 => $v1) {
          echo "$k $k1 ";
          if ($k1=='args') {
            echo "(";
            foreach($v1 as $k2 => $v2) {
              if ($k2>0)
              echo ',';
              echo "'$v2'";
            }
            echo ")\n";
          } else
            echo " $v1";
          echo "\n";
        }
      }
      die("userContext not initialized");
    }

    $xq_regCount=0;
    $xq_return='';

    /*
     * $formID vazio indica primeira solicita��o de lista
     * de mensagens sendo requirida pelo cliente yeapf.js
     */
    if ($formID=='') {
      $formID=md5('ym'.y_uniqid());
      $userContext->RegisterFormID($messagePeekerInterval);
    }

    //$messages=xq_produceReturnLinesFromArray($xq_return_array,$xq_regCount,true);

    // messages vindos do pr�prio usu�rio tem prioridade sobre os enviados pelo resto
    // ent�o eles n�o entram no processamento natural da pilha

    if ( ($a=='peekMessage') || ($targetUser==$u) )  {
        $messageList = $userContext->PeekMessages();
        $aSourceUserID=$u;
        $aMessage=$message;
        $aWParam=$wParam;
        $aLParam=$lParam;
        do {

          _dumpY(16,0,"@ sending $aSourceUserID, $aMessage, $aWParam, $aLParam");

          $xq_return_array = qy_msgProc($aSourceUserID, $aMessage, $aWParam, $aLParam);

          if (count($xq_return_array)>0)
            $xq_return.=xq_produceReturnLinesFromArray($xq_return_array, $xq_regCount, true, '', $xq_prefix, $xq_postfix);

          $moreFeed=false;

          $msg=array_shift($messageList);
          if ($msg>'') {
            $aSourceUserID=getNextValue($msg,';');
            $aMessage=getNextValue($msg,';');
            $aWParam=getNextValue($msg,';');
            $aLParam=getNextValue($msg,';');
            $moreFeed=($aMessage>'');
          }

        } while ($moreFeed);

    } else if ($a=='postMessage') {
      if ($targetUser=='*') {
        $aux=unquote($broadcastCondition);
        $varName=getNextValue($aux,'=');
        $varValue=getNextValue($aux,'=');

        $userContext->BroadcastMessage($varName, $varValue, $message, $wParam, $lParam);
      } else
        $userContext->PostMessage($targetUser, $message, $wParam, $lParam);
    }

  }

  function addMessageHandler($mh)
  {
    global $__messagesHandler;

    if ($mh!='qy_msgProc')
      if (!in_array($mh,$__messagesHandler)) {
        _dumpY(16,0,"registering '$mh' as message handler");
        array_push($__messagesHandler,$mh);
      }
  }

  function produceRestOutput($jsonData)
  {
    global $callback, $callbackId, $scriptSequence, $userMsg;

    if ((!isset($callbackId)) || ($callbackId==''))
      $callbackId = 'null';
    if ((!isset($scriptSequence)) || ($scriptSequence==''))
      $scriptSequence = '0';
    if ((!isset($callback)) || ($callback==''))
      $callback='ycomm.dummy';

    $context=array(  'callbackId'=>$callbackId,
                     'scriptSequence'=>$scriptSequence  );
    $context=json_encode($context);

    $script="
      if (typeof $callback == 'function') {
        $callback(200, 0, $jsonData, '$userMsg', $context);
      } else
        console.warn(\"'$callback' callback function was not found\");
    ";
    return $script;
  }

  function ryeapf($a)
  {
    global $callback;
    extract(xq_extractValuesFromQuery());

    $ret=array();

    if ($a=='ping') {
      $ret['serverTime'] = date('U');
    } else if ($a=='serverTime') {
      $ret['serverTime'] = date('Y-m-d H:i:s');
    }

    $jsonRet = json_encode($ret);

    echo produceRestOutput($jsonRet);
  }

  function yeapfAppEvents(&$s, $a)
  {
    global $userContext, $withoutHeader, $aBody,
           $withoutHeader, $currentSubject ;

    if ($s=='yeapf') {
      switch($a) {
        case 'getAppHeader':
          $withoutHeader=true;
          $aBody='e_app_header.html';
          break;
        case 'getMainBody':
        case 'buildMainBody':
            $withoutHeader=true;
            $aBody='e_main_body.html';
          break;
        case 'getAppFooter':
            $withoutHeader=true;
            $aBody='e_footer_body.html';
          break;
        case 'logoff':
        case 'exit':
            $userContext->logoff();
            $withoutHeader=true;
            $aBody='f_logoff.html';
          break;
        default:
          if (isset($userContext)) {
            if ($s=='') {
              $userContext->loadUserVars('currentSubject');
              $s=$currentSubject;
            } else {
              if ($s!='yeapf') {
                $currentSubject=$s;
                $userContext->addUserVars('currentSubject');
              }
            }
          }
          break;
      }
    }
  }

  addEventHandler('yeapfAppEvents');

?>

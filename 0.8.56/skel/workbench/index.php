<?php
  $dbConnect="no";
  (@include_once("yeapf.php")) or die("yeapf not configured<br><a href='configure.php'>Click here to configure</a>");
  (@include_once("lib/simple_html_dom/simple_html_dom.php")) or die("This software requires 'simple_html_dom'");
  (@include_once("lib/mecha-cms/minifier.php")) or die("This software requires 'mecha-cms' minifier");
 
  function getEssentialKey($fileName) {
    global $tp_config;
    $ret=false;
    foreach($tp_config['essentials'] as $key=>$value) {
      $file=substr("$value", 0, strpos($value, ":"));
      if ($file==$fileName) {
        $ret=$key;
      }
    }
    return $ret;
  } 

  function declareAsEssential($fileName, $filePath) {

  }

  function fileModified($base, $fileName) {
    $ret=false;
    if (file_exists("$fileName")) {
      $mt=filemtime("$fileName");
      $ret = $mt>$base;
    }
    return $ret;
  }

  function fileNameTag($fileName, $modified=false) {
    global $tp_config;

    $bName=basename($fileName);
    $filePath=dirname($fileName);

    $essential=(getEssentialKey($fileName)!==FALSE);

    $boxClass=$essential?"square":"square-o";
    $fileNameClass=$essential?"file-tag-essential":"file-tag";

    $extra="";
    if ($modified)
      $extra="style='text-decoration: underline;'";

    return "<div class='file-tag' $extra><i class='fa fa-$boxClass'></i>&nbsp;<a class='btnToggleEssentialFile' data-page='$bName' data-path='$filePath'>$bName</a></div>";
  }

  function fileModifiedTag($base, $fileName) {
    $ret="";
    if (fileModified($base,$fileName)) {
      $ret=fileNameTag($fileName);
    }
    return $ret;
  }


  function deleteFiles($dBody)
  {

    unlink("production/$dBody/i_$dBody.html");
    unlink("production/$dBody/i_$dBody.min.html");
    unlink("production/$dBody/$dBody.php");
    unlink("production/$dBody/$dBody.min.php");
    if (file_exists("$dBody.files")) {
      $fileList = file("$dBody.files");
      foreach($fileList as $f) {
        $f=str_replace("\n", "", $f);
        // echo "<div>file: $f</div>";
        unlink("$f");
      }
      unlink("$dBody.files");
    } else {
      unlink("production/$dBody/js/$dBody.js");
      unlink("production/$dBody/js/$dBody.min.js");
      unlink("production/$dBody/css/$dBody.css");
      unlink("production/$dBody/css/$dBody.min.css");
    }

    rmdir("production/$dBody/css");
    rmdir("production/$dBody/js");
    rmdir("production/$dBody");

    unlink("download/$dBody.zip");
  }

  $wbTitle = basename(getcwd());
  if (file_exists("tp.config")) {
    $tp_config = parse_ini_file("tp.config");
  } else {
    $tp_config=array();
  }

  if (!isset($tp_config['essentials'])) {
    $tp_config['essentials']=[];
  }

  if ((isset($dBody)) && ($dBody!='null')) {
    unlink("www/i_$dBody.html");
    unlink("www/$dBody.php");

    if (file_exists("$dBody.files")) {
      $fileList = file("$dBody.files");
      foreach($fileList as $f) {
        unlink("www/$dBody/$f");
      }
      unlink("$dBody.files");
    } else {
      unlink("www/js/$dBody.js");
      unlink("www/css/$dBody.css");
    }

    deleteFiles($dBody);

  } else if ((isset($xBody)) && ($xBody!='null')) {
    $xErase = isset($xErase)?intval($xErase)>0:0;
    $xMinified = isset($xMinified)?intval($xMinified)>0:0;
    deleteFiles($xBody);

    if (file_exists("www/js/app.js")) {
      $ap_mt_1=filemtime("www/js/app.js");
      $ap_mt_2=file_exists("production/js/app.js")?filemtime("production/js/app.js"):$ap_mt_1-1;
      if ($ap_mt_2<$ap_mt_1) {
        copy("www/js/app.js", "production/js/app.js");
      }
    }

    if (!$xErase) {
      $auxFiles = array();

      // $html = file_get_html("www/i_$xBody.html");

      $html = file_get_contents("www/i_$xBody.html");
      $html = str_replace("\n", '\n', $html);
      $html = str_get_html($html);

      $php  = _file("www/$xBody.php");

      @mkdir("production/$xBody", 0777, true);
      @mkdir("download", 0777, true);

      $extension='';
      if ($xMinified)
        $extension='.min';

      $newHTMLname = "production/$xBody/".str_replace(".html",    "$extension.html", "i_$xBody.html");
      $newPHPname  = "production/$xBody/$xBody.php";

      $html_out="";
      foreach ($html->find('div.section') as $elem) {
        $html_out.=str_replace('\n', "\n", minify_html($elem, $xMinified))."\n";

        foreach($elem->find("script,link,img") as $script) {
          $srcFile="";
          $srcType=$script->tag;
          if (isset($script->src)) {
            $srcFile=$script->src;
          } else if(isset($script->href)) {
            $srcFile=$script->href;
          }
          // echo "<div>$srcFile</div>";

          if ($srcFile>"") {
            if (file_exists("www/$srcFile")) {
              $newName=$srcFile;
              if ($srcType=="img") {
                $fileContent=file_get_contents("www/$srcFile");
              } else {
                $fileContent=_file("www/$srcFile");
                if ($xMinified) {
                  if (strpos($srcFile, ".min")==0) {
                    setlocale(LC_ALL,'en_US.UTF-8');
                    $ext = pathinfo($srcFile, PATHINFO_EXTENSION);
                    $newName=str_replace(".$ext", "$extension.$ext", $srcFile);
                    $fileContent=minify_js($fileContent);
                  }
                }
              }
              $dir=dirname($newName);
              if (!is_dir("production/$xBody/$dir")) {
                @mkdir("production/$xBody/$dir", 0777, true);
              }

              file_put_contents("production/$xBody/$newName", $fileContent);
              $auxFiles[] = "production/$xBody/$newName";

              $ts=date("U");

              $html_out = str_replace("src=$srcFile", "src='$xBody/$newName?ts=$ts'", $html_out);
              $html_out = str_replace("src='$srcFile'", "src='$xBody/$newName?ts=$ts'", $html_out);
              $html_out = str_replace("src=\"$srcFile\"", "src=\"$xBody/$newName?ts=$ts\"", $html_out);
            }
          }
        }
      }

      /*
        pronto:
          impedir a geração/eliminação de página de forma concomitante
          indicar a ordem das páginas (ao menos qual é a inicial)
          indicar quais os js e css obrigatorios em 'todas' as paginas (ou -o que é o mesmo- no projeto)
        falta:
          indicar se https é obrigatorio
          impedir que um mesmo js seja carregado mais de uma vez no aplicativo
          manter o cabeçalho feito pelo usuário. ou seja, a parte entre o <body> e o 'tnContainer'
          avisar ao aplicativo sobre uma pagina modificada. compilar essa página imediatamente.
          botao para compilacao em cada pagina
          permitir marcar uma pagina como indisponivel
          mostrar data/hora criação e ultima modificação
      */

      file_put_contents($newHTMLname, $html_out);
      file_put_contents($newPHPname,  $php);

      $zip = new ZipArchive();
      if ($zip->open("download/$xBody.zip", ZipArchive::CREATE)) {
        $zip->addFile($newHTMLname);
        $zip->addFile($newPHPname);
        foreach($auxFiles as $f)
          $zip->addFile($f);
        $zip->close();
      } else {
        echo "<div>ZipArchive 'download/$xBody.zip' cannot be created";
      }

      $fileList = join($auxFiles,"\n");
      file_put_contents("$xBody.files", $fileList);
    }

    $pageBody=$html_out;
    if (!file_exists("production/e_index_sample.html")) {
      $GLOBALS['pageSourceCode']=$pageBody;
    } else {
      $GLOBALS['pageSourceCode']="";
      //$html = file_get_html("production/e_index_sample.html");

      $html = file_get_contents("production/e_index_sample.html");
      $html = str_replace("\n", '\n', $html);
      $html = str_get_html($html);

      $subst=0;
      $tabNdx=1;
      $tnTabs=array();
      $scriptsList=array();
      $stylesList=array();

      function getScripts(&$html, $elem) {
        global $scriptsList, $stylesList;

        foreach($elem->find('script') as $script) {
          $scriptName = basename($script->src).'?';
          $scriptName = substr($scriptName, 0, strpos($scriptName, '?'));

          $scriptsList[$scriptName]=$script->src;
          $html=str_replace($script, "", $html);
        }

        foreach($elem->find('link') as $script) {
          $styleName = basename($script->href).'?';
          $styleName = substr($styleName, 0, strpos($styleName, '?'));

          $stylesList[$styleName]=$style->href;
          $html=str_replace($script, "", $html);
        }
      }

      foreach ($html->find('div.tnTab') as $elem) {
        $elemId=$elem->id;
        getScripts($html, $elem);

        $_ndx=($elemId=="vw_".$tp_config['first_page'])?0:++$tabNdx;
        if ($elemId=="vw_$xBody") {
          if (!$xErase) {
            $page_html=str_get_html($pageBody);
            foreach($page_html -> find('div') as $divElem) {
              getScripts($pageBody, $divElem);
            }
            $tnTabs[$_ndx]=$pageBody;
            $subst++;
          }
        } else {
          /* preserve tnTabs without id */
          $elem=str_replace('\n', "\n", $elem);
          $tnTabs[$_ndx]=$elem;
        }
      }

      if (!$xErase) {
        /* add the page */
        if ($subst==0) {
          $html=str_get_html($pageBody);
          foreach($elem = $html->find('div.tnTab')) {
            getScripts($pageBody, $elem);
            $elemId = $elem->id;
            $_ndx=($elemId=="vw_".$tp_config['first_page'])?0:++$tabNdx;
            $tnTabs[$_ndx]=$pageBody;
          }
        }
      }

      asort($tnTabs);

      foreach($tnTabs as $k=>$tab) {
        $GLOBALS['pageSourceCode'].="\n$tab\n";
      }

    }

    if (!file_exists("e_index_sample.html")) {
      copy("tp_app_index.html", "e_index_sample.html");
      echo "<div>e_index_sample.html file created on workbench folder</div>";
    }


    ksort($scriptsList);
    ksort($stylesList);

    $scriptsList = array_unique($scriptsList);
    $stylesList  = array_unique($stylesList);

    $scripts='';
    foreach($scriptsList as $name=>$location) {
      $scripts.="\t<script charset='utf-8' src='$location'></script>\n";
    }

    $styles='';
    foreach($stylesList as $name=>$location) {
      $styles.="\t<link href='$location' charset='utf-8' rel='stylesheet' type='text/css'>\n";
    }

    $e_index_sample=_file("e_index_sample.html");
    file_put_contents("production/e_index_sample.html", $e_index_sample);

  }

  if (!isset($aBody)) {
    if (isset($newPageName)) {
      $newPageName=trim($newPageName);
      if ($newPageName>'') {
        @mkdir("www/js", 0777, true);
        @mkdir("www/css", 0777, true);

        if (!file_exists("www/i_$newPageName.html")) {
          /* creating html file */
          $newPage=_file("tp_skel.html");
          file_put_contents("www/i_$newPageName.html", $newPage);
          chmod("www/i_$newPageName.html", 0777);

          if (!file_exists("www/js/$newPageName.js")) {
            /* creating js file */
            $scriptName=ucfirst($newPageName);
            $newScript = _file("tp_skel.js");
            file_put_contents("www/js/$newPageName.js", $newScript);
            chmod("www/js/$newPageName.js", 0777);
          }

          if (!file_exists("www/css/$newPageName.css")) {
            /* creating css file */
            $scriptName=ucfirst($newPageName);
            $newScript = _file("tp_skel.css");
            file_put_contents("www/css/$newPageName.css", $newScript);
            chmod("www/css/$newPageName.css", 0777);
          }

          if ((!file_exists("www/$newPageName.php")) && (file_exists("www/slotEmptyImplementation.php"))) {
            /* creating php file */
            $scriptName=ucfirst($newPageName);
            $GLOBALS['s']=$newPageName;
            $newScript = _file("www/slotEmptyImplementation.php");
            file_put_contents("www/$newPageName.php", $newScript);
            chmod("www/$newPageName.php", 0777);
          }
        }

      }
    }

    if (isset($essentialFilename)) {
      $_key=getEssentialKey($essentialFilename);
      if ($_key!==FALSE) {
        unset($tp_config['essentials'][$_key]);
      } else {
        $tp_config['essentials'][]="$essentialFilename:$essentialFilepath";
      }
      write_ini_file($tp_config, "tp.config");
    }

    if (isset($setFirstPage)) {
      if ((isset($tp_config['first_page'])) && ($tp_config['first_page']==$setFirstPage))
        unset($tp_config['first_page']);
      else
        $tp_config['first_page']="$setFirstPage";
      write_ini_file($tp_config, "tp.config");
    }

    $menu="";
    $n=0;

    foreach(glob('www/i_*') as $fileName) {
      $n++;
      $downloadBtn='';
      $eliminateBtn='';
      $auxFileName=substr($fileName,4);
      $dBody=basename(substr($auxFileName,2), ".html");
      if (file_exists("download/$dBody.zip")) {
        $downloadBtn="<a class='btn btn-default' data-page='$dBody' href='download/$dBody.zip'><i class='fa fa-download'></i></a>";
        $eliminateBtn="<a class='btn btn-warning btnDeleteDist' data-page='$dBody'><i class='fa fa-minus-square'></i></a>";
      }



      $fmList     = "";
      $fmModified = false;
      $jsList     = array();
      $phpList    = array();
      $cssList    = array();
      if (file_exists("$dBody.files")) {
        $fileList = file("$dBody.files");
      } else {
        $fileList = array("www/$d.php", "www/css/$d.css", "www/js/$d.js");
      }
      foreach($fileList as $prodFile) {
        $prodFile=str_replace("\n", "", $prodFile);
        $workbenchFile=str_replace("production/$dBody/", "www/", $prodFile);
        $m_prod = filemtime($prodFile);
        $m_modified = fileModified($m_prod, $workbenchFile);
        $fmList.=fileNameTag($workbenchFile, $m_modified);
        $fmModified |= $m_modified;

        $file_info=pathinfo($workbenchFile);
        $ext=strtolower($file_info['extension']);
        if ($ext=='php')
          $phpList[]=$workbenchFile;
        else if ($ext=='js')
          $jsList[]=$workbenchFile;
        else if ($ext=='css')
          $cssList[]=$workbenchFile;
      }
      $m_html  = filemtime("production/$dBody/i_$dBody.html");
      $fmList.=fileModifiedTag($m_html, "$fileName");

      $cl1="";
      $cl2="";
      if ($fmModified) {
        $cl1=" highight-blue";
        $cl2=" spin";
      }

      $firstButtonClass="";
      if (isset($tp_config['first_page'])) {
        if ($dBody==$tp_config['first_page']) {
          $firstButtonClass="active";
        }
      }

      $btnFirstPage  = "<button class='btn btn-default btnFirstPage $firstButtonClass' data-page='$dBody'><i class='fa fa-home' data-page='$dBody'></i></button>";

      $btnDeletePage = "<button class='btn btn-danger btnDeletePage highight-red' data-page='$dBody'><i class='fa fa-trash' data-page='$dBody'></i></button>";

      $btnZipSection = "<button class='btn btn-default btnCreateZipDist' id='btnZipSection$n' data-page='$dBody'><i class='fa fa-file-zip-o' data-page='$dBody'></i></button>";

      $btnExtractSecion = "<button class='btn btn-default btnCreateDist $cl1' id='btnExtractSection$n' data-page='$dBody'><i class='fa fa-puzzle-piece $cl2' data-page='$dBody'></i></button>";

      $menu.="<div class='col-lg-5'>
                <div class='panel panel-default'>
                  <div class='panel-heading'>
                    $btnDeletePage
                    <div class='btn-group pull-right'>
                      $btnFirstPage
                      $btnZipSection
                      $btnExtractSecion
                      $downloadBtn
                      $eliminateBtn
                    </div>
                  </div>
                  <div class='panel-body'>
                    <a href='$fileName'>$auxFileName</a><div class='col-lg-12'>$fmList</div>
                  </div>
                </div>
              </div>";
    }

    processFile("tp_index");
  } else {
    chdir("www");
    processFile("tp_testPage");
  }
?>
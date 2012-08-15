<?php

/**************************************************************************
*  Copyright notice
*
*  Copyright 2012 Logic Works GmbH
*
*  Licensed under the Apache License, Version 2.0 (the "License");
*  you may not use this file except in compliance with the License.
*  You may obtain a copy of the License at
*
*  http://www.apache.org/licenses/LICENSE-2.0
*  
*  Unless required by applicable law or agreed to in writing, software
*  distributed under the License is distributed on an "AS IS" BASIS,
*  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
*  See the License for the specific language governing permissions and
*  limitations under the License.
*  
***************************************************************************/

class lw_de_navigation extends projectBasis 
{
    function __construct() 
    {
        $this->config = lw_registry::getInstance()->getEntry("config");
        require_once( $this->config['path']['framework'].'markdown/markdown.php');
    }

    /**
     *Verteilerfunktion
     * @return string 
     */
    function execute($isLoggedIn) 
    {
        $this->isLoggedIn = $isLoggedIn;
        return $this->show();
    }
    
    /**
     *Anzeige des Plugins.
     * @return string 
     */
    function show() 
    {
        $this->response->useJquery();
        $template = file_get_contents(dirname(__FILE__) . '/../templates/navigation.tpl.html');
        $tpl = new lw_te($template);
        
        $this->setErrorMessages($tpl);
        
        #lösch bestätigung (verzeichnis, welches dateien enthält)
        if($this->request->getAlnum("confirm") == "dirdeletion"){
            $this->confirmDelete($tpl);    
        }

        $tpl->reg("startverzeichnis", $this->buildLink('navigation', 'show', 'home'));
        
        if ($this->directoryObject->getRelativePath()) {
            $tpl->setIfVar("back");
            $tpl->reg("backdir" , $this->directoryUp($this->directoryObject->getRelativePath())); # ins oberverzeichnis wechseln
        }
        
        if($this->isLoggedIn == true) {
            $tpl->setIfVar("showadditems");
            $tpl->reg("adddir" , $this->buildLink('directory', 'new', $this->directoryObject->getRelativePath()));
            $tpl->reg("addfile" , $this->buildLink('file', 'new', $this->directoryObject->getRelativePath()));
        }
        $tpl->reg("dirinfoformaction", $this->buildLink('navigation', 'show', $this->directoryObject->getRelativePath()));
        
        $this->executeFileInformation($tpl);
        
        $tpl->reg("activedirectory", $this->directoryObject->getName());
        $tpl->reg("breadcrumbcontent", $this->createBreadcrumbDisplay());
        $tpl->reg("directorycontent", $this->createContentDisplay());
        return $tpl->parse();
    }
    
    function getPreparedDirectoryEntries()
    {
        $directories = $this->directoryObject->getDirectoryContents("dir");
        if (!empty($directories)) {
            foreach ($directories as $directory) {
                $directory = lw_directory::getInstance($directory->getPath());
                $lowerDirs = $directory->getDirectoryContents("dir");
                if($lowerDirs == "") {
                    $deleteButton = "show";
                }
                else {
                    $deleteButton = "hide";
                }

                $contentArray[] = array(
                    "name" => $directory->getName(),
                    "datum" => $directory->getDate(),
                    "type" => "dir",
                    "deleteButton" => $deleteButton,
                    "size" => " "
                );
            }
        }
        return $contentArray;
    }
    
    function getPreparedFilesEntries() 
    {
        $files = $this->directoryObject->getDirectoryContents("file");
        if (!empty($files)) {
            foreach ($files as $file) {
                $contentArray[] = array(
                    "name" => $file->getFilename(),
                    "datum" => $file->getDate(),
                    "type" => $file->getExtension(),
                    "deleteButton" => "",
                    "size" => $file->getSize()
                );
            }
        }
        return $contentArray;
    }
    
    function setErrorMessages($tpl)
    {
        # Fehlermeldungen
        if($this->request->getAlnum("error") == "filerename"){
            $tpl->setIfVar("error");
            $tpl->reg("errorMsg", "ERROR: The file [ ".$this->request->getRaw("selectedfile")." ] cannot be renamed, because another file with the same name exists!");
        }
        if($this->request->getAlnum("error") == "dirrename"){
            $tpl->setIfVar("error");
            $tpl->reg("errorMsg", "ERROR: The directory [ ".$this->request->getRaw("selecteddir")." ] cannot be renamed, because another directory with the same name exists!");
        };
        if($this->request->getAlnum("error") == "adddir"){
            $tpl->setIfVar("error");
            $tpl->reg("errorMsg", "ERROR: The directory [ ".$this->request->getRaw("dirname")."/ ] cannot be added, because another directory with the same name exists!");
        };
        if($this->request->getAlnum("error") == "url"){
            $tpl->setIfVar("error");
            $tpl->reg("errorMsg", "ERROR: Url Manipulation!");
        };        
    }
    
    function confirmDelete($tpl)
    {
        $tpl->setIfVar("confirm");
        if($this->request->getRaw("reldir") == "home") {
            $tpl->reg("reldir", "home");
        }
        else {
            $tpl->reg("reldir", $this->request->getRaw("reldir"));
        }
        $tpl->reg("deleteANonEmptyDir", $this->request->getRaw("delete"));
        $tpl->reg("dir", $this->request->getRaw("delete"));
        $tpl->reg("reldir", $this->request->getRaw("reldir"));
        $tpl->reg("execute-link",$this->config["url"]["client"] . "index.php?index=" . $this->request->getInt("index") . "&module=");  
    }
    
    function executeFileInformation($tpl)
    {
        $tpl->reg("contentdirectoryinfo" , Markdown($this->directoryObject->getInfoFileContent()));
        $tpl->reg("contentdirectoryinfoTA" , $this->directoryObject->getInfoFileContent());
        
        if($this->request->getAlnum("sent") && $this->request->getAlnum("sent") == 1) {
            $this->directoryObject->writeInfoFileContent($this->request->getRaw("textareaDirInfo"));
            lw_object::pageReload($this->config["url"]["client"] . "index.php?index=".$this->request->getInt("index")."&module=navigation&cmd=show&dir=".$this->directoryObject->getRelativePath());
        }
    }
    
    /**
     *Baut das Templatestück zusammen, womit der Verzeichnisinhalt dargestellt wird.
     * @param array $contentArray
     * @return string 
     */
    function createContentDisplay() 
    {
        $DirArray = $this->getPreparedDirectoryEntries();
        $FileArray = $this->getPreparedFilesEntries();
        if (is_array($FileArray) && is_array($DirArray)) {
            $contentArray = array_merge($DirArray, $FileArray);
        } 
        elseif(is_array($FileArray)){
            $contentArray = $FileArray;
        } 
        else {
            $contentArray = $DirArray;
        }
        
        $i = 1;
        if (!empty($contentArray)) {
            foreach ($contentArray as $content) {
                $template = file_get_contents(dirname(__FILE__) . '/../templates/navigation_directory_content.tpl.html');
                $tpl = new lw_te($template);
                
                if ($content["name"] != "." && $content["name"] != ".." && $content["name"] != "lwdirinfo.txt") {
                    
                    #anzeigenamen kürzen, falls die zulang sind (um verschub im template zuvermeiden)
                    $tpl->reg("nameimdisplay", $content["name"]);
                    $tpl->reg("name", $content["name"]); #bezeichnung für die execute links rename/delete
                    $tpl->reg("datum", $content["datum"]);
                    $tpl->reg("type", $content["type"]);
                    $tpl->reg("size", $content["size"]);
                    $tpl->reg("execute-link",$this->config["url"]["client"] . "index.php?index=" . $this->request->getInt("index") . "&module=");  
                    
                    if ($content["type"] == "dir") {
                        $this->fillDirectoryRow($tpl, $content);
                    } 
                    else { # CONTENT-TYPE FILE 
                        $this->fillFileRows($tpl, $content);
                    }

                    if ($i % 2 != 0) {
                        $tpl->reg("oddeven", "odd");
                    } 
                    else {
                        $tpl->reg("oddeven", "even");
                    }
                    $output.= $tpl->parse();
                    $i++;
                }
            }
        }
        return $output;
    }
    
    function fillDirectoryRow($tpl, $content)
    {
        $angepasstername = substr($content["name"], 0, strlen($content["name"]) -1);
        $tpl->reg("angepasstername",$angepasstername);
        $tpl->reg("fileOrDir","Verzeichnis");
        $tpl->setIfVar("dir"); # setzt folder icon
                        
        #logginstatus prüfen (nur eingeloggte user dürfen änderungen vornehmen
        if($this->isLoggedIn == true) {
            $tpl->setIfVar("showrenamebutton");

            if($content["deleteButton"] == "show") {
                $tpl->setIfVar("showdeletebutton");
            }
            elseif ($content["deleteButton"] == "hide") {
                $tpl->setIfVar("hidedeletebutton");    
            }
        }
        else {
            $tpl->setIfVar("hidedeletebutton");
            $tpl->setIfVar("hiderenamebutton");
        }
                  
        
        if (substr($this->directoryObject->getRelativePath(), -1) != "/") {
            $parentdir = $this->directoryObject->getRelativePath()."/";
        }
        else {
            $parentdir = $this->directoryObject->getRelativePath();
        }
        $tpl->reg("dir", $parentdir.$content["name"]);
        $tpl->reg("link", $this->buildLink("navigation", "show", $parentdir.$content["name"]));
    }
    
    function fillFileRows($tpl, $content)
    {
        if($this->isLoggedIn == true){
            $tpl->setIfVar("showrenamebutton");
            $tpl->setIfVar("showdeletebutton");
        }
        $tpl->reg("fileOrDir","Datei");
        $angepasstername = substr($content["name"], 0, strpos($content["name"], $content["type"]) -1);
        $tpl->reg("angepasstername",$angepasstername);

        $tpl->reg("dir", $this->directoryObject->getRelativePath());
        $tpl->reg("file", $content["name"]);
        $tpl->reg("link",$this->buildLink('file', 'download', $this->request->getRaw("dir"), $content["name"]));
        $tpl->setIfVar("file");
    }
    
    /**
     *Baut das Templatestück zusammen, womit die Breadcrumb-Navigation dargestellt wird. 
     * @param string $strdir
     * @return string
     */
    function createBreadcrumbDisplay() 
    {
        $strdir = $this->directoryObject->getRelativePath()."/";
        if ($strdir != false) {
            $strdir = str_replace("//", "/", $strdir);
            //die($strdir);
            
            #dir string zerlegen
            $explodeDir = explode("/", $strdir, -1);
            $k = 0;
            foreach ($explodeDir as $dir) {
                if (strlen(trim($dir))>0) {
                    $template = file_get_contents(dirname(__FILE__) . '/../templates/navigation_breadcrumb_content.tpl.html');
                    $tpl = new lw_te($template);
                    $tpl->reg("dirname", $explodeDir[$k]);
                    #dir string zusammensetzen ( mit den benötigten elementen)
                    for ($i = 0; $i <= $k; $i++) {
                        $paramDir .= $explodeDir[$i]."/" ;
                    }
                    $tpl->reg("link", $this->config["url"]["client"] . "index.php?index=" . $this->request->getInt("index") . "&module=navigation&cmd=show&dir=" . $paramDir);
                    $k++;
                    $paramDir = ""; # $paramDir reset
                    $output .= $tpl->parse();
                }
            }
            return $output;
        }
    }
    
    /**
     *Baut den Verzeichnislink zusammen, um in das Oberverzeichnis wechseln zu können.
     * @param string $strdir
     * @return string 
     */
    function directoryUp($strdir)
    {
        if($strdir == false || $strdir == "home") {
            return $this->config["url"]["client"] . "index.php?index=" . $this->request->getInt("index") . "&module=navigation&cmd=show&dir=home";
        }
        else {
            $explodeDir = explode("/", $strdir, -1);
            $index = count($explodeDir);
            
            if($index >= 2) {
                $lastEntry = $index -1;
                unset($explodeDir[$lastEntry]);
                foreach ($explodeDir as $dir) {
                    $paramDir .= $dir."/";
                }
                return $this->config["url"]["client"] . "index.php?index=" . $this->request->getInt("index") . "&module=navigation&cmd=show&dir=" . $paramDir;
            }
            else {
                return $this->config["url"]["client"] . "index.php?index=" . $this->request->getInt("index") . "&module=navigation&cmd=show&dir=home";
            }
        }
    }
}
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

    function execute($isLoggedIn) 
    {
        $this->isLoggedIn = $isLoggedIn;
        return $this->show();
    }
    
    /**
     * Die Benutzeroberfläche des Plugins wird zurückgegeben..
     * 
     * @return string 
     */
    function show() 
    {
        $this->response->useJquery();
        $template = file_get_contents(dirname(__FILE__) . '/../templates/navigation.tpl.html');
        $tpl = new lw_te($template);
        
        $this->setErrorMessages($tpl);
        
        if($this->request->getAlnum("confirm") == "dirdeletion"){
            $this->confirmDelete($tpl);    
        }

        $tpl->reg("startverzeichnis", $this->buildLink('navigation', 'show', 'home'));

        if ($this->directoryObject->getRelativePath()) {
            if($this->treeView == false){
                $tpl->setIfVar("back");
                $tpl->reg("backdir" , $this->directoryUp($this->directoryObject->getRelativePath())); # ins oberverzeichnis wechseln
            }
        }

        $path = str_replace($this->config["path"]["web_resource"]."lw_directorynavigator/".$this->directoryObject->getHomeDir(), "", $this->directoryObject->getPath());
        $length = strlen($path) - strlen($this->directoryObject->getName());
        $relPath = substr($path, 0, $length);

        if($this->treeView) {
            $tpl->setIfVar("home");
            if ($this->getDirLevel($path)==0) {
                $tpl->setIfVar("actualdir");
            }
            else {
                $tpl->setIfVar("opendir");
            }
            $tpl->reg("home_margin_left" , -8);
            $tpl->reg("home_link" , $this->buildLink('navigation', 'show', 'home'));
        }
        
        if($this->isLoggedIn == true) {
            $tpl->setIfVar("showadditems");
            if($this->getDirLevel($path) < $this->maxDirLevels){
                $tpl->setIfVar("showNewDir");
                $tpl->reg("adddir" , $this->buildLink('directory', 'new', $path));
            }
            $tpl->reg("addfile" , $this->buildLink('file', 'new', $path));
        }
        $tpl->reg("dirinfoformaction", $this->buildLink('navigation', 'show', $path));
        
        $this->executeFileInformation($tpl);
        
        if($this->directoryObject->getName() == $this->directoryObject->getHomeDir() || $this->directoryObject->getName() == "lw_directorynavigator/"){
            $tpl->reg("activedirectory", "home/");
        }
        else{
            $tpl->reg("activedirectory", $this->directoryObject->getName());
        }
        $tpl->reg("directorycontent", $this->createDirectoryContentDisplay());
        $tpl->reg("filecontent", $this->createFileContentDisplay());
        
        if ($this->showBreadcrumb) {
            $tpl->setIfVar("showbreadcrumb");
            $tpl->reg("breadcrumbcontent", $this->createBreadcrumbDisplay());
        }
        
        return $tpl->parse();
    }

    /**
     * Der Inhalt, des ausgewählten Verzeichnisses, wird abgefragt und in einem Array zusammengestellt.
     * Dateien werden nicht in diesem Array gelistet.
     * 
     * @return array 
     */
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
    
    /**
     * Die gesamte Verzeichnisstruktur wird abgefragt und in einem Array zusammen gestellt.
     * Dateien werden nicht in diesem Array gelistet.
     * 
     * @param int $indent
     * @param string $directoryPath
     * @return array 
     */
    function getPreparedDirectoryEntriesTreeView($indent, $directoryPath)
    {
        $dir = lw_directory::getInstance($directoryPath);
        $directories = $dir->getDirectoryContents("dir");

        if (!empty($directories)) {
            $indent++;
            foreach ($directories as $directory) {
                $lowerDirs = $directory->getDirectoryContents("dir");
                if($lowerDirs == "") {
                    $deleteButton = "show";
                }
                else {
                    $deleteButton = "hide";
                }
                
                $path = str_replace($this->config["path"]["web_resource"]."lw_directorynavigator/".$this->directoryObject->getHomeDir(), "", $directory->getPath());
                
                $length = strlen($path) - strlen($directory->getName());
                $relPath = substr($path, 0, $length);
                
                $this->dirArray[]  = array(
                    "name" => $directory->getName(),
                    "datum" => $directory->getDate(),
                    "type" => "dir",
                    "deleteButton" => $deleteButton,
                    "size" => " ",
                    "indent" => $indent,
                    "fullPath" => $path,
                    "relpath" => $relPath
                );
                $this->getPreparedDirectoryEntriesTreeView($indent, $directory->getPath());
            }
        }
        return $this->dirArray;
    }
    
    /**
     * Es wird geprüft, ob der übergebene relative Pfad, in dem Pfad des ausgewählten Verzeichnisses zufinden ist.
     * 
     * @param string $relpath
     * @return boolean
     */
    function isInRequestDir($relpath = false)
    {
        if($relpath != false){
            if(strpos($this->request->getRaw("dir") , $relpath)!== false){
                return true;
            }
        }
        
    }
    
    /**
     * Das ausgewählte Verzeichnis wird ausgelesen und die vorhandenen Dateien in einem Array aufgelistet.
     * 
     * @return type 
     */
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
    
    /**
     * Ausgabe der benötigten Fehlermeldungen.
     * 
     * @param string $tpl 
     */
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
    
    /**
     * Ausgabe der Aufforderung an den Nutzer, das Löschen, zu bestätigen.
     * 
     * @param string $tpl 
     */
    function confirmDelete($tpl)
    {
        $path = str_replace($this->config["path"]["web_resource"]."lw_directorynavigator/".$this->directoryObject->getHomeDir(), "", $this->directoryObject->getPath());
        
        $tpl->setIfVar("confirm");
        if($this->request->getRaw("reldir") == "home") {
            $tpl->reg("reldir", "home");
        }
        else {
            $tpl->reg("reldir", $this->request->getRaw("reldir"));
        }
        $tpl->reg("deleteANonEmptyDir", $this->request->getRaw("delete"));
        $tpl->reg("dir", urlencode($path));
        $tpl->reg("reldir", urlencode(""));
        $tpl->reg("execute-link",  $this->getExecuteLink());  
    }
    
    /**
     * Die Verzeichnisinfo wird ausgegeben und bei Änderungen gespeichert.
     * 
     * @param string $tpl 
     */
    function executeFileInformation($tpl)
    {
        $tpl->reg("contentdirectoryinfo" , Markdown($this->directoryObject->getInfoFileContent($this->useOnlyHomeDir_lwdirinfo)));
        $tpl->reg("contentdirectoryinfoTA" , $this->directoryObject->getInfoFileContent($this->useOnlyHomeDir_lwdirinfo));

        if($this->request->getAlnum("sent") && $this->request->getAlnum("sent") == 1) {
            $this->directoryObject->writeInfoFileContent($this->request->getRaw("textareaDirInfo"), $this->useOnlyHomeDir_lwdirinfo);
            lw_object::pageReload($this->buildLink("navigation", "show", $this->directoryObject->getRelativePath()));
        }
    }
    
    /**
     * Die Verzeichnisübersichtstabelle wird erstellt.
     * 
     * @return string 
     */
    function createDirectoryContentDisplay() 
    {
        if($this->treeView == true){
            $DirArray = $this->getPreparedDirectoryEntriesTreeView(0, $this->directoryObject->getHomePath());
        }
        else{
            $DirArray = $this->getPreparedDirectoryEntries();
        }
        #print_r($DirArray);die();
        if(is_array($DirArray)) {
            $contentArray = $DirArray;
        }
        
        $i = 0;
        if (!empty($contentArray)) {
            foreach ($contentArray as $content) {
                if($this->treeView == true){
                    if($content["indent"] < $this->maxDirLevels + 1 && $content["indent"] == 1 | $content["relpath"] == $this->request->getRaw("dir") | $this->isInRequestDir($content["relpath"]) == true){
                        $i++;
                        $output.= $this->getDirContentDisplayElements($content,$i);
                    }
                }
                else{
                    $i++;
                    $output.= $this->getDirContentDisplayElements($content,$i);
                }
            }
        }
        return $output;
    }
    
    /**
     * Eine einzelne Verzeichniszeile wird erstellt.
     * 
     * @param array $content
     * @param int $i
     * @return string 
     */
    function getDirContentDisplayElements($content,$i)
    {
        $template = file_get_contents(dirname(__FILE__) . '/../templates/navigation_directory_content.tpl.html');
        $tpl = new lw_te($template);

        $tpl->reg("nameimdisplay", $content["name"]);
        $tpl->reg("name", $content["name"]); #bezeichnung für die execute links rename/delete
        $tpl->reg("datum", $content["datum"]);
        $tpl->reg("type", $content["type"]);
        $tpl->reg("size", $content["size"]);
        $tpl->reg("execute-link",$this->getExecuteLink());  

        if($this->treeView == true){
            $tpl->setIfVar("treeView");
            $tpl->reg("margin-left", ($content["indent"] * 25) - 25);
            $tpl->setIfVar("showCompleteRow");
            $tpl->setIfVar("showAllInfos");
            $this->fillDirectoryRow($tpl, $content);
        }
        
        if($this->treeView == false && $this->checkDirLevel($this->directoryObject->getRelativePath()) == true){
            $tpl->setIfVar("showCompleteRow");
            $tpl->setIfVar("showAllInfos");
            $this->fillDirectoryRow($tpl, $content);
        }
        
        $tpl->reg("oddeven", "odd");
        if ($i % 2 == 0) {
            $tpl->reg("oddeven", "even");
        } 

        $output.= $tpl->parse();

        return $output;
    }

    /**
     * Die Dateiübersichtstabelle wird erstellt.
     * 
     * @return string 
     */
    function createFileContentDisplay()
    {
        $FileArray = $this->getPreparedFilesEntries();
        
        if(is_array($FileArray)){
            $contentArray = $FileArray;
        } 
        $i = 1;
        if (!empty($contentArray)) {
            foreach ($contentArray as $content) {
                $template = file_get_contents(dirname(__FILE__) . '/../templates/navigation_directory_content.tpl.html');
                $tpl = new lw_te($template);
                
                if ($content["name"] != "." && $content["name"] != ".." && $content["name"] != "lwdirinfo.txt") {
                    $tpl->reg("nameimdisplay", $content["name"]);
                    $tpl->reg("name", $content["name"]); #bezeichnung für die execute links rename/delete
                    $tpl->reg("datum", $content["datum"]);
                    $tpl->reg("type", $content["type"]);
                    $tpl->reg("size", $content["size"]);
                    $tpl->reg("execute-link",$this->getExecuteLink());  
                    
                    $tpl->setIfVar("showCompleteRow");
                    $tpl->setIfVar("showAllInfos");
                    $this->fillFileRows($tpl, $content);
            
                    $tpl->reg("oddeven", "odd");
                    if ($i % 2 == 0) {
                        $tpl->reg("oddeven", "even");
                    } 
                    
                    $output.= $tpl->parse();
                    $i++;
                }
            }
        }
        if (!$output) {
            $output = file_get_contents(dirname(__FILE__) . '/../templates/navigation_nofiles.tpl.html');
        }
        return $output;
    }
    
    /**
     * Basis Informationen der anzuzeigenden Spalten werden ins Template eingefügt.
     * 
     * @param string $tpl
     * @param array $content 
     */
    function tplRegBasicInfos($tpl , $content)
    {
        $tpl->reg("nameimdisplay", $content["name"]);
        $tpl->reg("name", $content["name"]); #bezeichnung für die execute links rename/delete
        $tpl->reg("datum", $content["datum"]);
        $tpl->reg("type", $content["type"]);
        $tpl->reg("size", $content["size"]);
        $tpl->reg("execute-link",$this->getExecuteLink());
    }
    
    /**
     * Das Template für die Verzeichnis Spalte wird mit Inhalt gefüllt.
     * 
     * @param string $tpl
     * @param array $content 
     */
    function fillDirectoryRow($tpl, $content)
    {
        $angepasstername = substr($content["name"], 0, strlen($content["name"]) -1);
        $tpl->reg("angepasstername",$angepasstername);
        $tpl->reg("fileOrDir","Verzeichnis");
        
        
        if($this->isSameDir($content["relpath"].$content["name"], $this->request->getRaw("dir"))) {
            $tpl->setIfVar("actualdir");
        }
        elseif($this->isInRequestDir($content['relpath'].$content["name"]) == true){
            $tpl->setIfVar("opendir");
        }
        else{
            $tpl->setIfVar("dir");
        }
                        
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
               
      
        if($this->treeView == true){
            $parentdir = $content["relpath"];
        }
        else{
            $parentdir = str_replace($this->config["path"]["web_resource"]."lw_directorynavigator/".$this->directoryObject->getHomeDir(), "", $this->directoryObject->getPath());
        }
        
        $tpl->reg("dir", urlencode($parentdir.$content["name"]));
        $tpl->reg("link", $this->buildLink("navigation", "show", $parentdir.$content["name"]));
    }
    
     /**
     * Das Template für die Datei Spalte wird mit Inhalt gefüllt.
     * 
     * @param string $tpl
     * @param array $content 
     */
    function fillFileRows($tpl, $content)
    {
        if($this->isLoggedIn == true){
            $tpl->setIfVar("showrenamebutton");
            $tpl->setIfVar("showdeletebutton");
        }
        $tpl->reg("fileOrDir","Datei");
        $angepasstername = substr($content["name"], 0, strpos($content["name"], $content["type"]) -1);
        $tpl->reg("angepasstername",$angepasstername);

        $path = str_replace($this->config["path"]["web_resource"]."lw_directorynavigator/".$this->directoryObject->getHomeDir(), "", $this->directoryObject->getPath());
        
        $tpl->reg("dir", urlencode($path));
        $tpl->reg("file", $content["name"]);
        $tpl->reg("link",$this->buildLink('file', 'download', $this->request->getRaw("dir"), $content["name"]));
        $tpl->setIfVar("file");
    }
    
    /**
     * Die Brotkrumennavigation wird erstellt.
     * 
     * @return string 
     */
    function createBreadcrumbDisplay() 
    {
        $strdir = str_replace($this->config["path"]["web_resource"]."lw_directorynavigator/".$this->directoryObject->getHomeDir(), "", $this->directoryObject->getPath());
        
        if ($strdir != false) {
            $strdir = str_replace("//", "/", $strdir);
            $explodeDir = explode("/", $strdir, -1);
            $k = 0;
            foreach ($explodeDir as $dir) {
                if (strlen(trim($dir))>0) {
                    $template = file_get_contents(dirname(__FILE__) . '/../templates/navigation_breadcrumb_content.tpl.html');
                    $tpl = new lw_te($template);
                    $tpl->reg("dirname", $explodeDir[$k]);
                    for ($i = 0; $i <= $k; $i++) {
                        $paramDir .= $explodeDir[$i]."/" ;
                    }
                    $tpl->reg("link", $this->buildLink("navigation", "show", $paramDir));
                    $k++;
                    $paramDir = "";
                    $output .= $tpl->parse();
                }
            }
            return $output;
        }
    }
    
    /**
     * Der Link, um in das Oberverzeichnis, wechseln zu können wird erstellt.
     * 
     * @param string $strdir
     * @return string 
     */
    function directoryUp($strdir)
    {
        if($strdir == false || $strdir == "home") {
            return $this->buildLink("navigation", "show", "home");
        }
        else {
            $strdir = $strdir."/";
            
            $explodeDir = explode("/", $strdir, -1);
            $index = count($explodeDir);
            
            if($index >= 2) {
                $lastEntry = $index -1;
                unset($explodeDir[$lastEntry]);
                foreach ($explodeDir as $dir) {
                    $paramDir .= $dir."/";
                }
                return $this->buildLink("navigation", "show", $paramDir);
            }
            else {
                return $this->buildLink("navigation", "show", "home");
            }
        }
    }
}

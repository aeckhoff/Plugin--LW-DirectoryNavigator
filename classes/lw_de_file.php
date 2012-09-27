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

class lw_de_file extends projectBasis 
{

    function __construct() 
    {
    }

    /**
     * Verteilerfunktion
     */
    function execute($isLoggedIn) 
    {
        switch ($this->request->getAlnum("cmd")) {
            case "new" :
                if($isLoggedIn == true) {
                    $output = $this->showUploadForm();
                }
                break;

            case "edit" :
                if($isLoggedIn == true) {
                    $this->edit();
                }
                break;

            case "delete" :
                if($isLoggedIn == true) { 
                    $this->delete();
                }
                break;

            case "download" :
                $this->download();
                break;
            
            default:
                throw new Exception('unknown command in module "file": '.$this->request->getAlnum("cmd"));
        }
        return $output;
    }

    /**
     * Es wird geprüft ob die Datei umbenannt werden kann, ansonsten wird eine Fehlermeldung angezeigt.
     */
    function edit()
    {
        if (!$this->checkIfFilenameExists()) {
            $this->rename();
        }
        else {
            $options = array(
                "error" => "filerename",
                "selectedfile" => $this->fileObject->getFilename(),
                "rename" => $this->request->getRaw("rename").".".$this->fileObject->getExtension()
            );
            lw_object::pageReload($this->buildLink('navigation', 'show', $this->request->getRaw("dir"), false, $options));
        }
    }
    
    /**
     * Die Datei wird umbenannt.
     * (Wenn die Datei sich in der erlaubten Verzeichnistiefe befindet)
     * 
     * @throws Exception 
     */
    function rename() 
    {
//        $path = str_replace($this->config["path"]["web_resource"]."lw_directorynavigator/".$this->directoryObject->getHomeDir(), "", $this->directoryObject->getPath());
//        $length = strlen($path) - strlen($this->directoryObject->getName());
//        $relPath = substr($path, 0, $length);
        
        if($this->isFileInMaxDirLevel($this->fileObject->getPath()) == true){
            $this->fileObject->rename($this->request->getRaw("rename") . "." . $this->fileObject->getExtension());
            $this->redirectToActualList();
        }else{
            throw new Exception("rename file not allowed");
        }
    }
    
    /**
     * Die ausgewählte Datei wird gelöscht.
     * (Wenn die Datei sich in der erlaubten Verzeichnistiefe befindet)
     * 
     * @throws Exception 
     */
    function delete() 
    {
        if($this->isFileInMaxDirLevel($this->fileObject->getPath()) == true){
            $this->fileObject->delete();
            $this->redirectToActualList();
        }
        else{
            throw new Exception("delete file not allowed");
        }
    }    
    
    /**
     * Die ausgewählte Datei wird zum Download bereitgestellt.
     * (Wenn die Datei sich in der erlaubten Verzeichnistiefe befindet)
     * 
     * @throws Exception 
     */
    function download() 
    {
        #die($this->directoryObject->getPath().$this->request->getRaw("file"));
        #die($this->fileObject->getFullPath());
        
        $path = str_replace($this->config["path"]["web_resource"]."lw_directorynavigator/".$this->directoryObject->getHomeDir(), "", $this->directoryObject->getPath());
        #$length = strlen($path) - strlen($this->directoryObject->getName());
        #$relPath = substr($path, 0, $length);
        $directory = lw_directory::getInstance($this->config["path"]["web_resource"]."lw_directorynavigator/".$this->directoryObject->getHomeDir().$path);
        #die($directory->getPath().$this->fileObject->getFilename());
        
        if($this->checkDirLevel($path) == true){
            $content = file_get_contents($directory->getPath().$this->fileObject->getFilename());
            header('Content-Type: application/octet-stream;');
            header("Content-Disposition: attachment; filename=\"" . $this->fileObject->getFilename() . "\";");
            die($content);
        }
        else{
            throw new Exception("download file not allowed");
        }
    }    
    
    /**
     * Es wird geürpft, ob der Dateiname bereits in dem aktuellem Verzeichnis vorhanden ist.
     * 
     * @return boolean 
     */
    function checkIfFilenameExists() 
    {
        $filename = $this->fileObject->getFilename();
        $renamename = $this->request->getRaw("rename").".".$this->fileObject->getExtension();
        if ($filename != $renamename) {
            if (is_file($this->fileObject->getPath.$renamename)) {
                return true;
            }
            return false;
        }
        $this->redirectToActualList();
    }

    /**
     * Das Uploadformular wird angezeigt.
     * 
     * @return string
     */
    function showUploadForm() 
    {
        $tpl = new lw_te(file_get_contents(dirname(__FILE__) . '/../templates/upload.tpl.html'));
        $tpl->reg("backlink", $this->buildLink("navigation", "show", $this->request->getRaw("dir")) . "%2F");
        $tpl->reg("formactionlink", $this->buildLink("file", "new", $this->request->getRaw("dir")));
        $tpl->reg("maxfilesize", ini_get('post_max_size')."b");
        
        $fileDataArray = $this->request->getFileData("uploadfield");
        $error = $this->showErrorMessages($tpl, $fileDataArray);

        if ($this->request->getAlnum("sent") && $this->request->getAlnum("sent") == 1 && !$error) {
            $uploadExtension = ".".lw_io::getFileExtension($fileDataArray["name"]);
            $this->checkUploadFilenameandUpload($fileDataArray,$uploadExtension);
        }            
        return $tpl->parse();
    }

    /**
     * Die entsprechende Fehlermeldung wird ausgegeben.
     * 
     * @param string $tpl
     * @param array $fileDataArray
     * @return boolean 
     */
    function showErrorMessages($tpl, $fileDataArray)
    {
        if($this->request->getAlnum("refuse") && $this->request->getAlnum("refuse") == 1){
            $errormsg = "File already available! ";
        }
        if($this->request->getAlnum("refuse") && $this->request->getAlnum("refuse") == 2){
            $errormsg = "Filetype not allowed.";
        }
        if ($this->request->getAlnum("sent") && $this->request->getAlnum("sent") == 1) {

            if($this->request->getAlnum("radiobutton_upload") == "") {
                 $errormsg = "no option was chosen!";
            }
            if ($this->request->getFileData("uploadfield") && empty($fileDataArray["name"])) {
                 $errormsg = " No files selected.";
            } 
            else {
                if($fileDataArray["size"] > $this->maxFileSizePostInBytes(ini_get('post_max_size'))) {
                     $errormsg = "The file is too big!";
                }
                else{
                    return false;
                }
            }
        } 
        
        if ($errormsg) {
            $tpl->setIfVar("error");
            $tpl->reg("errormsg", $errormsg);
            return true;
        }
        return false;
    }
    
    /**
     * Aus dem Dateinamen der hochzuladenen Datei werden unerwünschte Zeichen entfernt.
     * Die Endung der hochzuladenen Datei wird geprüft.
     * 
     * @param type $fileDataArray
     * @param type $uploadExtension 
     */
    function checkUploadFilenameandUpload($fileDataArray,$uploadExtension) 
    {
        if ($this->directoryObject->isExtensionAllowed($uploadExtension)) {
        
            $fileDataArray["name"] = preg_replace("/[^A-Z .a-z0-9_-]/", "", $fileDataArray["name"]);
            
            if (is_file($this->directoryObject->getPath().$fileDataArray["name"])) {
                $filename = $this->getFilenameInCaseOfExistingSameFilename($fileDataArray);
            }
            else {
                $filename = $fileDataArray['name'];
            }
            $this->directoryObject->addFile($fileDataArray['tmp_name'], $filename);
            $this->redirectToActualList($this->directoryObject->getName());
        }
        else {
            lw_object::pageReload($this->buildLink('file', 'new', $this->request->getRaw("dir"), false, array("refuse"=>2)));
        }
    }
    
    /**
     * Ist der Dateiname, der hochzuladenen Datei, vorhanden, wird die entsprechende Verfahrensweise angewand.
     * 
     * @param array $fileDataArray
     * @return type 
     */
    function getFilenameInCaseOfExistingSameFilename($fileDataArray) 
    {
        switch ($this->request->getAlnum("radiobutton_upload")) {

            case "ueberschreiben":
                return $fileDataArray['name'];

            case "ablehnen":
                lw_object::pageReload($this->buildLink('file', 'new', $this->request->getRaw("dir"), false, array("refuse"=>1)));

            case "suffix":
                return $this->directoryObject->getNextFilename($fileDataArray['name']);
        }
    }

    /** 
     * Die übergebene Dateigröße wird in Bytes umgerechnet.
     * 
     * @param float $size
     * @return int 
     */
    function maxFileSizePostInBytes($size) 
    {
        $stringEnd = strtolower(substr($size, strlen($size)-1, 1));
        $sizeOhneStringEnd =substr($size, 0, strlen($size)-1);
        switch($stringEnd) {
            case 'g':
                $sizeOhneStringEnd *= 1024;
            case 'm':
                $sizeOhneStringEnd *= 1024;
            case 'k':
                $sizeOhneStringEnd *= 1024;
        }
        return $sizeOhneStringEnd;
    }
}


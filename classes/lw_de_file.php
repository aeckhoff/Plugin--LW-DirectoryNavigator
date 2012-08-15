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
     * Benennt eine ausgewählte Datei um.
     */
    function rename() 
    {
        $this->fileObject->rename($this->request->getRaw("rename") . "." . $this->fileObject->getExtension());
        $this->redirectToActualList();
    }
    
    /**
     * löscht die ausgewählte datei 
     */
    function delete() 
    {
        $this->fileObject->delete();
        $this->redirectToActualList();
    }    
    
    /**
     * download der ausgewählten datei 
     */
    function download() 
    {
        $content = file_get_contents($this->fileObject->getFullPath());
        header('Content-Type: application/octet-stream;');
        header("Content-Disposition: attachment; filename=\"" . $this->fileObject->getFilename() . "\";");
        die($content);
    }    
    
    /**
     * überprüft ob der dateiname bereists im verzeichnis zu finden ist 
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
     * zeigt das uploadforumlar an
     * @return string 
     */
    function showUploadForm() 
    {
        $tpl = new lw_te(file_get_contents(dirname(__FILE__) . '/../templates/upload.tpl.html'));
        
        $tpl->reg("backlink", $this->config["url"]["client"] . "index.php?index=" . $this->request->getInt("index") . "&module=navigation&cmd=show&dir=" . $this->request->getRaw("dir"));;    
        $tpl->reg("formactionlink", $this->config["url"]["client"] . "index.php?index=" . $this->request->getInt("index") . "&module=file&cmd=new&dir=" . $this->request->getRaw("dir"));
        $tpl->reg("maxfilesize", ini_get('post_max_size')."b");
        
        $fileDataArray = $this->request->getFileData("uploadfield");
        $error = $this->showErrorMessages($tpl, $fileDataArray);

        if ($this->request->getAlnum("sent") && $this->request->getAlnum("sent") == 1 && !$error) {
            $uploadExtension = ".".lw_io::getFileExtension($fileDataArray["name"]);
            $this->checkUploadFilenameandUpload($fileDataArray,$uploadExtension);
        }            
        return $tpl->parse();
    }

    function showErrorMessages($tpl, $fileDataArray)
    {
        if($this->request->getAlnum("refuse") && $this->request->getAlnum("refuse") == 1){
            $errormsg = "Datei wurde abgelehnt, Dateiname existiert bereits.";
        }
        if($this->request->getAlnum("refuse") && $this->request->getAlnum("refuse") == 2){
            $errormsg = "Unerlaubter Daten-Typ.";
        }
        if ($this->request->getAlnum("sent") && $this->request->getAlnum("sent") == 1) {

            if($this->request->getAlnum("radiobutton_upload") == "") {
                 $errormsg = "Keine Option ausgewählt, welches Verfahren angewand werden soll, wenn der Dateiname bereits existiert !";
            }
            if ($this->request->getFileData("uploadfield") && empty($fileDataArray["name"])) {
                 $errormsg = "Es wurde keine Datei ausgewählt";
            } 
            else {
                if($fileDataArray["size"] > $this->maxFileSizePostInBytes(ini_get('post_max_size'))) {
                     $errormsg = "Die Datei ist zu groß!";
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
     *prüft ob der name der hochzuladenden datei bereits existiert. 
     * @param array $fileDataArray
     * @param string $uploadExtension 
     */
    function checkUploadFilenameandUpload($fileDataArray,$uploadExtension) 
    {
        if ($this->directoryObject->isExtensionAllowed($uploadExtension)) {
        
            $fileDataArray["name"] = preg_replace("/[^A-Z .a-z0-9_-]/", "", $fileDataArray["name"]);
            
            if (is_file($this->directoryObject->getActualPath().$fileDataArray["name"])) {
                $filename = $this->getFilenameInCaseOfExistingSameFilename($fileDataArray);
            }
            else {
                $filename = $fileDataArray['name'];
            }
            $this->directoryObject->addFile($fileDataArray['tmp_name'], $filename);
            $this->redirectToActualList();
        }
        else {
            lw_object::pageReload($this->buildLink('file', 'new', $this->request->getRaw("dir"), false, array("refuse"=>2)));
        }
    }
    
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
     *umrechnung der max uploadsize in bytes
     * @param string $size
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


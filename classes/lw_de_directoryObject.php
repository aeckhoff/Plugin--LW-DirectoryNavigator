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

class lw_de_directoryObject 
{
    private $dir;
    private $homeDir;
    private $configuration;
    private $directoryObject = array();
    private $actualDirectoryObject;
    private $parentDirectoryObject;

    private function __construct($dir)
    {
        if ($dir == "home") {
            $dir = '';
        }
        $this->dir = $this->filterVar($dir);
    }
    
    /**
     * Die Instanz eines Verzeichnisobjekts wird zurückgegeben.
     * 
     * @param string $dir
     * @param string $home
     * @param string $config
     * @return object 
     */
    public function getInstance($dir, $home, $config) 
    {
        if (!is_object($this->directoryObjectContainer[$dir.$home])) {
            $this->directoryObjectContainer[$dir.$home] = new lw_de_directoryObject($dir);
            $this->directoryObjectContainer[$dir.$home]->setHomeDir($home);
            $this->directoryObjectContainer[$dir.$home]->setConfiguration($config);
            $this->directoryObjectContainer[$dir.$home]->init();
        }
        
        return $this->directoryObjectContainer[$dir.$home];
    }
    
    /**
     * Das Startverzeichnis wird gesetzt.
     * 
     * @param string $dir 
     */
    public function setHomeDir($dir)
    {
        if($dir != false){
            $stringEnd = substr($dir, strlen($dir) -1 ,1);
            if($stringEnd == "/"){
                $this->homeDir = $dir;
            }
            else{
                $this->homeDir = $dir . "/";
            }
        }
    }
    
    /**
     * Das Startverzeichnis wird zurückgegeben.
     * 
     * @return string 
     */
    public function getHomeDir()
    {
        return $this->homeDir;
    }
    
    /**
     * Der Pfad des Startverzeichnisses wird zurückgegeben.
     * 
     * @return string 
     */
    public function getHomePath()
    {
        return $this->configuration["path"]["web_resource"]."lw_directorynavigator/".$this->homeDir;
    }
    
    public function setConfiguration($config)
    {
        $this->configuration = $config;
    }
    
    /**
     * Initialisierungsfunktion
     * 
     * @throws Exception 
     */
    public function init()
    {
        $path = $this->configuration["path"]["web_resource"]."lw_directorynavigator/".$this->homeDir.$this->dir;
    
        if (!is_writeable($path)) {
            throw new Exception("directory is not writeable: ".$path);
        }
        
        $realpatha = realpath($this->configuration["path"]["web_resource"]."lw_directorynavigator/");
        $realpathb = realpath($path);
        
        if (substr($realpathb,0,strlen($realpatha)) != $realpatha) {
            throw new Exception("invalid directory: ".$path);
        }
        
        if (!is_dir($path)) {
            throw new Exception("invalid directory: ".$path);
        }
        $this->directoryObject = lw_directory::getInstance($path);
    }
    
    /**
     * der aktuelle Verzeichnispfad wird zurückgegeben.
     * 
     * @return string 
     */
    function getActualPath() 
    {
        return $this->directoryObject->getBasepath().$this->directoryObject->getName();
    }

    function getPath()
    {
        return $this->directoryObject->getPath();
    }
    
    /**
     * Der aktuelle Verzeichnisname wird zurückgegeben.
     * 
     * @return string 
     */
    function getName()
    {
        $name = $this->directoryObject->getName();
        if (!$name) {
            return "home";
        }
        return $name;
    }
    
    /**
     * Ausgehend von dem aktuellen Verzeichnis wird das Oberverzeichnis instanziert.
     * 
     * @return boolean 
     */
    function getActualParentObject()
    {
        if (!$this->dir) {
            return false;
        }
        if (!$this->parentObject) {
            $parentDir = str_replace($this->configuration["path"]["web_resource"]."lw_directorynavigator/".$this->homeDir, "", $this->directoryObject->getPath());
            $this->parentObject = lw_de_directoryObject::getInstance($parentDir, $this->homeDir, $this->configuration); 
        }
        return $this->parentObject;
    }
 
    /**
     * Der relative Verzeichnispfad wird zurückgegeben.
     * 
     * @return string
     */
    function getRelativePath()
    {
        if (!$this->relativePath) {
            $this->relativePath = str_replace($this->configuration["path"]["web_resource"]."lw_directorynavigator/".$this->homeDir, "", $this->getActualPath());
            
            if (substr($this->relativePath, -1) == "/") {
                $this->relativePath =substr($this->relativePath, 0, -1);
            }
        }
        return $this->relativePath;
    }
    
    /**
     * Unerwünschte Zeichen werden aus der Variable entfernt.
     * 
     * @param string $var
     * @return string 
     */
    function filterVar($var) 
    {
        $var = str_replace("..", "", $var);
        $var = str_replace("//", "", $var);
        $var = preg_replace("/[^A-Z .\/a-z0-9_-]/", "", $var);
        return $var;
    }
    
    /**
     * Der Verzeichnisinhalt wird ausgelesen.
     * 
     * @param string $type
     * @return array
     */
    function getDirectoryContents($type=false) 
    {
        return $this->directoryObject->getDirectoryContents($type);
    }

    /**
     * Das aktuelle Verzeichnis wird umbenannt.
     * 
     * @param string $name
     * @return boolean 
     */
    function rename($name) 
    {
        return $this->directoryObject->rename($name);
    }
    
    /**
     * In das aktuelle Verzeichnis wird ein neues Verzeichnis hinzugefügt.
     * 
     * @param string $name
     * @return boolean 
     */
    function add($name) 
    {
        return $this->directoryObject->add($name);
    }
    
    /**
     * In das aktuelle Verzeichnis wird eine neue Datei hinzugefügt.
     * 
     * @param string $temp
     * @param string $file
     * @return boolean 
     */
    function addFile($temp, $file) 
    {
        return $this->directoryObject->addFile($temp, $file);
    }
    
    /**
     * Bei Dateinamensgleichheit wird der nächste freie Name zurückgegeben (Suffix):
     * 
     * @param string $file
     * @return string 
     */
    function getNextFilename($file) 
    {
        return $this->directoryObject->getNextFilename($file);
    }
    
    /**
     * Das aktuelle Verzeichnis wird gelöscht. Parameter = true  -> Verzeichnis und beinhaltete Daeien
     * werden gelöscht.
     * 
     * @param boolean $flag
     * @return boolean 
     */
    function delete($flag=false) 
    {
        return $this->directoryObject->delete($flag);
    }
    
    /**
     * Die lwdirinfo des aktuellen Verzeichnisses wird ausgelesen.
     * Ist der Prameter = true , dann wird nur die lwdirinfo des Startverzeichnis
     * ausgelesen.
     * 
     * @param boolean $useOnlyHomeDir_lwdirinfo
     * @return string 
     */
    function getInfoFileContent($useOnlyHomeDir_lwdirinfo = false)
    {
        if($useOnlyHomeDir_lwdirinfo == true){
            if (!$this->infoContent && is_file($this->configuration["path"]["web_resource"]."lw_directorynavigator/".$this->homeDir."/lwdirinfo.txt")) {
                $this->infoContent = file_get_contents($this->configuration["path"]["web_resource"]."lw_directorynavigator/".$this->homeDir."/lwdirinfo.txt");
            }
        }else{
            if (!$this->infoContent && is_file($this->getActualPath()."/lwdirinfo.txt")) {
                $this->infoContent = file_get_contents($this->getActualPath()."/lwdirinfo.txt");
            }
        }
        return $this->infoContent;
    }
    
    /**
     * Änderungen an der lwdirinfo werden übernommen.
     * Parameter = true -> alle Änderungen werden in der lwdirinfo des Startverzeichnisses
     * gespeichert.
     * 
     * @param type $content
     * @param type $useOnlyHomeDir_lwdirinfo 
     */
    function writeInfoFileContent($content,$useOnlyHomeDir_lwdirinfo = false)
    {
        if($useOnlyHomeDir_lwdirinfo == true){
            lw_io::writeFile($this->configuration["path"]["web_resource"]."lw_directorynavigator/".$this->homeDir."/lwdirinfo.txt", $content);
        }else{
            lw_io::writeFile($this->getActualPath()."/lwdirinfo.txt", $content);
        }
    }
    
    /**
     * Array mit erlaubten Dateiendungen wird erstellt.
     * 
     * @param type $extensionString 
     */
    function setAllowedExtensions($extensionString)
    {   
        $this->allowedExtensionArray = explode(",", $extensionString);
        $this->disabledExtensionArray = explode(",", $this->configuration["directorynavigator"]["disabled_extensions"]);
    }
    
    /**
     * Die Dateiendung wird überprüft.
     * 
     * @param string $extension
     * @return boolean 
     */
    function isExtensionAllowed($extension)
    {
        if (in_array($extension, $this->disabledExtensionArray)) {
            return false;
        }
        if ($this->allowedExtensionArray[0] == "*" || in_array($extension, $this->allowedExtensionArray)) {
            return true;
        }
        return false;
    }    
}

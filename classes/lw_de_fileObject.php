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

class lw_de_fileObject 
{

    private function __construct($dir, $file)
    {
        $this->dir = $dir;
        $this->file = $this->filterVar($file);
    }
    
    public function getInstance($dir, $file, $config) 
    {
        if (!is_object($this->fileObjectContainer[$dir->getActualPath().$file])) {
            $this->fileObjectContainer[$dir->getActualPath().$file] = new lw_de_fileObject($dir, $file);
            $this->fileObjectContainer[$dir->getActualPath().$file]->setConfiguration($config);
            $this->fileObjectContainer[$dir->getActualPath().$file]->init();
        }
        return $this->fileObjectContainer[$dir->getActualPath().$file];
    }
    
    function filterVar($var) 
    {
        $var = str_replace("..", "", $var);
        $var = str_replace("//", "", $var);
        $var = preg_replace("/[^A-Z .\/a-z0-9_-]/", "", $var);
        return $var;
    }    
    
    public function setConfiguration($config)
    {
        $this->configuration = $config;
    }
    
    public function init()
    {
        $path = $this->dir->getActualPath();
        if (!is_dir($path)) {
            throw new Exception("invalid directory: ".$path);
        }
        if (!is_file($path.$this->file)) {
            throw new Exception("invalid file: ".$path.$this->file);
        }
        $this->fileObject = lw_file::getInstance($path, $this->file);
    }
    
    function rename($name)
    {
        return $this->fileObject->rename($name);
    }
    
    function delete()
    {
        return $this->fileObject->delete();
    }
    
    function getExtension()
    {
        return $this->fileObject->getExtension();
    }
    
    function getFullPath()
    {
        return $this->fileObject->getPath().$this->fileObject->getFilename();
    }
    
    function getPath()
    {
        return $this->fileObject->getPath();
    }
    
    function getFilename()
    {
        return $this->fileObject->getFilename();
    }
    
}
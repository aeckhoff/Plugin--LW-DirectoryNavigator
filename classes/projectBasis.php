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

class projectBasis extends lw_object
{
    function setRequest($request)
    {
        $this->request= $request;
    }
    
    function setDatahandler($datahandler)
    {
        $this->datahandler = $datahandler;
    }
    
    function setDataBasehandler($databasehandler)
    {
        $this->db = $databasehandler;
    }
    
    function setConfig($config)
    {
        $this->config = $config;
    }
    
    function setLang($lang)
    {
        $this->lang = $lang;
    }
    
    function setRepository($repository)
    {
        $this->repository = $repository;
    }
    
    function setPluginName($pluginname)
    {
        $this->getPluginName = $pluginname;
    }
    
    function getPluginName()
    {
        return $this->getPluginName;
    }
    
    function setOid($oid)
    {
        $this->oid = $oid;
    }
    
    function getOid()
    {
        return $this->oid;
    }
    
    function setResponse($response)
    {
        $this->response = $response;
    }
    
    function hideBreadcrumb($bool)
    {
        if ($bool) {
            $this->hideBreadcrumb = true;
        }
        else {
            $this->hideBreadcrumb = false;
        }
    }
    
    /**
     * Klassen Verzeichnisobjekt
     * 
     * @param object $object 
     */
    function setDirectoryObject($object)
    {
        $this->directoryObject = $object;
    }
    
    /**
     * Klassen Dateiobjekt
     * 
     * @param object $object 
     */
    function setFileObject($object) 
    {
        $this->fileObject = $object;
    }
    
    /**
     * Es wird aus den Übergabeparametern eine URL zusammengebaut, die an die entsprechende
     * Stellen in dem Template eingesetzt wird.
     * 
     * @param string $module
     * @param string $cmd
     * @param string $dir
     * @param string $file
     * @param array $options
     * @return string 
     */
    function buildLink($module, $cmd, $dir=false, $file=false, $options=false)
    {
        $array['index'] = $this->request->getInt("index");
        $array['module'] = $module;
        $array['cmd'] = $cmd;
        if ($dir) {
            $array['dir'] = $dir;
        }
        if ($file) {
            $array['file'] = $file;
        }
        if (is_array($options)) {
            $array = array_merge($array, $options);
        }
        $url = $this->config["url"]["client"]."index.php?".http_build_query($array);
        $url = str_replace("&amp;", "&", $url);
        return $url;
    }
    
    /**
     * Link für navigation_direcotry_content Template ( Delete/Rename Links)
     * 
     * @return string
     */
    function getExecuteLink()
    {
        return $this->config["url"]["client"] . "index.php?index=" . $this->request->getInt("index") . "&module=" ;
    }
    
    /**
     * Weiterleitung zum Oberverzeichnis.
     */
    function redirectToParentList() 
    {
        $parentObject = $this->directoryObject->getActualParentObject();
        lw_object::pageReload($this->buildLink('navigation', 'show', $parentObject->getRelativePath()));
    }

    /**
     * Aktuelles Verzeichnis wird neu geladen. 
     */
    function redirectToActualList() 
    {
        lw_object::pageReload($this->buildLink('navigation', 'show', $this->directoryObject->getRelativePath()));
    }
    
    /**
     * Klassenvaribale wird gesetzt mit der Eingabe im Backend "Max. Verzeichnistiefe"
     * 
     * @param int $levels 
     */
    function setMaxDirLevels($levels) 
    {
        $this->maxDirLevels = $levels;
    }
    
    /**
     * Klassenvaribale wird gesetzt mit der Eingabe im Backend "Benutzerdefiniertes CSS verwenden"
     * 
     * @param int $levels 
     */
    function setUseOnlyHome_lwdirinfo($use) 
    {
        if($use == 1){
            $this->useOnlyHomeDir_lwdirinfo = true;
        }
    }
    
    /**
     * Klassenvaribale wird gesetzt mit der Eingabe im Backend "Baumstruktur anzeigen"
     * 
     * @param int $levels 
     */
    function setTreeView($use) 
    {
        if($use == 1){
            $this->treeView = true;
        }
    }
    
    /**
     * Prüft ob die Verzeichnistiefe, die angegebene Max.-Verzeichnistiefe, nicht überschreitet
     * @param string $relpath
     * @return boolean 
     */
    function checkDirLevel($relPath = false)
    {
        if($this->maxDirLevels == 0){
            return false;
        }
        if($relPath != false){

            $relPath = $relPath . "/";
            $explodeDir = explode("/", $relPath, -1);
            $index = count($explodeDir);
            
            if($index <= $this->maxDirLevels -1){
                return true;
            }
        }
        if($this->maxDirLevels != 0 && $relPath == false){
            return true;
        }
    }
    
    /**
     * Prüft ob die Datei sich innerhalb der erlaubten Max.Verzeichnistiefe befindet
     * @param string $filepath
     * @return boolean 
     */
    function isFileInMaxDirLevel($filepath)
    {
        $relFilePath = str_replace($this->config["path"]["resource"]."lw_directorynavigator/".$this->directoryObject->getHomeDir(), "", $filepath);

        if($relFilePath == false && $this->maxDirLevels == 0){
            return true;
        }
        
        if($relFilePath != false){
            $explodeDir = explode("/", $relFilePath, -1);
            $index = count($explodeDir);
            
            if($index > $this->maxDirLevels){
                return false;
            }
            else{
                return true;
            }
        }
        if($this->maxDirLevels != 0 && $relFilePath == false){
            return true;
        }
    }
}

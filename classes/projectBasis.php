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
    
    function setBreadcrumb($bool)
    {
        if ($bool) {
            $this->showBreadcrumb = true;
        }
        else {
            $this->showBreadcrumb = false;
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
        $path = str_replace($this->config["path"]["web_resource"]."lw_directorynavigator/".$this->directoryObject->getHomeDir(), "", $this->directoryObject->getPath());
                
        $length = strlen($path) - strlen($this->directoryObject->getName());
        $relPath = substr($path, 0, $length);
        
        lw_object::pageReload($this->buildLink('navigation', 'show', $relPath));
    }

    /**
     * Aktuelles Verzeichnis wird neu geladen. 
     */
    function redirectToActualList() 
    {
        $path = str_replace($this->config["path"]["web_resource"]."lw_directorynavigator/".$this->directoryObject->getHomeDir(), "", $this->directoryObject->getPath());
        
        lw_object::pageReload($this->buildLink('navigation', 'show', $path));
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
    
    function getDirLevel($path) 
    {
        $explodeDir = explode("/", $path);
        foreach($explodeDir as $dir) {
            if (strlen(trim($dir))>0) {
                $index++;
            }
        }       
        return $index;
    }
    
    function isSameDir($a, $b) 
    {
        $lasta = substr($a, -1);
        if ($lasta != "/") {
            $a = $a."/";
        }   
        $lastb = substr($b, -1);
        if ($lastb != "/") {
            $b = $b."/";
        }   
        if ($a == $b) {
            return true;
        }
        return false;
    }
    
    /**
     * Prüft ob die Verzeichnistiefe, die angegebene Max.-Verzeichnistiefe, nicht überschreitet
     * @param string $relpath
     * @return boolean 
     */
    function checkDirLevel($relPath = false)
    {
        if($this->maxDirLevels == 0) {
            return false;
        }
        if($relPath != false) {

            $relPath = $relPath . "/";
            $relPath = str_replace("//", "/", $relPath);
            $explodeDir = explode("/", $relPath, -1);
            $index = count($explodeDir);
            
            //if($index <= $this->maxDirLevels -1) {
            if($index <= $this->maxDirLevels) {
                return true;
            }
        }
        if($this->maxDirLevels != 0 && $relPath == false) {
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
    
//    /**
//    * Arrayfehler beim Key "relpath" werden korrigiert und das korrigierte Arraay zurückgegeben.
//    * @param array $array
//    * @return array 
//    */
//    function correctRelpathesInDirArray($array){
//        $count = count($array);
//        
//        #die("abufdas ".$count);
//        $gesamtfundstellen = "";
//        for($i = 0; $i <= $count; $i++){
//            $k = $i+1;
//            if($array[$i]["name"] == $array[$k]["name"] && $array[$i]["relpath"] == $array[$k]["relpath"]){
//                $fundstelle = $i . "," . $k . ",";
//                if(!empty($gesamtfundstellen)){
//                    $gesamtfundstellen = str_replace($i, $fundstelle, $gesamtfundstellen);
//                }
//                else{
//                    $gesamtfundstellen = $fundstelle;
//                }
//            }
//        }
//        
//        $fundstellenArray = explode(",", $gesamtfundstellen, -1);
//        $index = count($fundstellenArray);
//        unset($fundstellenArray[$index -1]);
//        
//        for($i = 0; $i <= $index; $i++){
//            $k = $i-1;
//            if($fundstellenArray[$i] -1 == $fundstellenArray[$k]){
//                $array[$fundstellenArray[$i]]["relpath"] = $array[$fundstellenArray[$k]]["relpath"].$array[$fundstellenArray[$k]]["name"];
//            }
//        }
//
//        return $array;
//    }   
}

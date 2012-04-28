<?php

/* * ************************************************************************
 *  Copyright notice
 *
 *  Copyright 1998-2009 Logic Works GmbH
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
 * ************************************************************************* */

class ltRepository {

    public function __construct()
    {
        $this->setConfig(lw_registry::getInstance()->getEntry('config'));
    }

    function setConfig($config)
    {
        $this->config = $config;
    }
    
    function setPluginDAO($plugindao=false) {
        if (!$plugindao){
            require_once(dirname(__FILE__).'/../repository/PluginDAO.php');
            $plugindao = new PluginDAO(lw_registry::getInstance()->getEntry('db'));
        }
        $this->PluginDAO = $plugindao;
    }

    function getPluginDAO() {
        if (!isset($this->PluginDAO)) {
            $this->setPluginDAO();
        }
        return $this->PluginDAO;
    }
    
    function setIntranetDAO($intranetdao=false) {
        if ($this->IntranetDAO) return true;
        if (!$intranetdao){
            require_once(dirname(__FILE__).'/../repository/IntranetDAO.php');
            $intranetdao = new IntranetDAO(lw_registry::getInstance()->getEntry('db'));
        }
        $this->IntranetDAO = $intranetdao;
    }

    function getIntranetDAO() {
        if (!isset($this->IntranetDAO)) {
            $this->setIntranetDAO();
        }
        return $this->IntranetDAO;
    }    
    
    function setTemplateDAO($templatedao=false) {
        if ($this->TemplateDAO) return true;
        if (!$templatedao){
            require_once(dirname(__FILE__).'/../repository/TemplateDAO.php');
            $templatedao = new TemplateDAO(lw_registry::getInstance()->getEntry('db'));
        }
        $this->TemplateDAO = $templatedao;
    }

    function getTemplateDAO() {
        if (!isset($this->TemplateDAO)) {
            $this->setTemplateDAO();
        }
        return $this->TemplateDAO;
    }
    
    function setListtoolDAO($listtooldao=false) {
        if ($this->ListtoolDAO) return true;
        if (!$listtooldao){
            require_once(dirname(__FILE__).'/../repository/ListtoolDAO.php');
            $listtooldao = new ListtoolDAO(lw_registry::getInstance()->getEntry('db'), $this->config);
        }
        $this->ListtoolDAO = $listtooldao;
    }

    function getListtoolDAO() {
        if (!isset($this->ListtoolDAO)) {
            $this->setListtoolDAO();
        }
        return $this->ListtoolDAO;
    }      
    
    public function deleteEntryByContainer($cid)
    {
        return $this->getPluginDAO()->deleteEntryByContainer($cid);
    }

    public function loadPluginData($plugin, $cid)
    {
        return $this->getPluginDAO()->loadPluginData($plugin, $cid);
    }

    public function savePluginData($plugin, $cid, $parameter=false, $content=false, $item_id=false)
    {
        return $this->getPluginDAO()->savePluginData($plugin, $cid, $parameter, $content, $item_id);
    }

    public function getAllAssignedIntranetsByObject($otype, $oid)
    {
        return $this->getIntranetDAO()->getAllAssignedIntranetsByObject($otype, $oid);
    }

    public function getAllAssignedUsersByObject($otype, $oid)
    {
        return $this->getIntranetDAO()->getAllAssignedUsersByObject($otype, $oid);
    }

    public function getTemplateList()
    {
        return $this->getTemplateDAO()->getTemplateList($otype, $oid);
    }    

    public function loadTemplateById($id)
    {
        return $this->getTemplateDAO()->loadTemplateById($id);
    }    
    
    public function loadListByOid($oid, $sorting=false, $onlypublished=true)
    {
        if ($sorting) {
            $sortby = $sorting;
        } else {
            $sortby = "opt1number";
        }
        return $this->getListtoolDAO()->loadListByOid($oid, $sortby, $onlypublished);
    }

    public function loadEntryById($id)
    {
        return $this->getListtoolDAO()->loadEntryById($id);
    }

    public function getPageIdByItemId($id)
    {
        return $this->getListtoolDAO()->getPageIdByItemId($id);
    }

    public function getContainerIdByItemId($id)
    {
        $data = $this->getListtoolDAO()->loadEntryById($id);
        return $data['category_id'];
    }

    public function deleteEntryById($id)
    {
        return $this->getListtoolDAO()->deleteEntryById($id);
    }

    public function addEntry($array)
    {
        $array['lw_object'] = 'lw_listtool';
        $array['opt1number'] = $this->getListtoolDAO()->getHighestSeqInPage($array['category_id']) + 1;

        $file1['name'] = $array['opt1file_name'];
        $file1['tmp'] = $array['opt1file_tmp'];
        unset($array['opt1file_name']);
        unset($array['opt1file_tmp']);        

        $file2['name'] = $array['opt2file_name'];
        $file2['tmp'] = $array['opt2file_tmp'];
        unset($array['opt2file_name']);
        unset($array['opt2file_tmp']);        
        
        $id = $this->getListtoolDAO()->insertEntry($array);

        if ($file1['name']) {
            $thumbnail = 'item_'.$id.'.'.lw_io::getFileExtension($file1['name']);
            $this->getListtoolDAO()->saveFile($file1['tmp'], $thumbnail);
            $array['opt1file'] = $thumbnail;
        }

        if ($file2['name']) {
            $this->getListtoolDAO()->saveFile($file2['tmp'], 'item_'.$id.'.file');
            $array['opt2file'] = $file2['name'];
            $array['opt3number'] = date("YmdHis");
        }
        
        return $this->getListtoolDAO()->updateEntry($array, $id);
    }

    public function saveEntryById($id, $array)
    {
        $array['lw_object'] = 'lw_listtool';
        unset($array['opt1bool']);

        if ($array['opt1file_name']) {
            $thumbnail = 'item_'.$id.'.'.lw_io::getFileExtension($array['opt1file_name']);
            $this->getListtoolDAO()->saveFile($array['opt1file_tmp'], $thumbnail);
            unset($array['opt1file_name']);
            unset($array['opt1file_tmp']);
            $array['opt1file'] = $thumbnail;
        }

        if ($array['opt2file_name']) {
            $name = $array['opt2file_name'];
            $this->getListtoolDAO()->saveFile($array['opt2file_tmp'], 'item_'.$id.'.file', true);
            unset($array['opt2file_name']);
            unset($array['opt2file_tmp']);
            $array['opt2file'] = $name;
            $array['opt3number'] = date("YmdHis");
        }
        
        return $this->getListtoolDAO()->updateEntry($array, $id);
    }
   
    public function saveSequence($id, $seq)
    {
        return $this->getListtoolDAO()->saveSequence($id, $seq);
    }

    public function deleteListByOid($oid)
    {
        $entries = $this->loadListByOid($oid);
        foreach ($entries as $entry) {
            $dir = lw_directory::getInstance($this->config['path']['listtool']);
            $files = $dir->getDirectoryContents('file');
            foreach ($files as $file) {
                if (strstr($file->getName(), 'item_'.$entry['id'].'.')) {
                    $file->delete();
                }
            }

            $dir = lw_directory::getInstance($this->config['path']['listtool'] . "archive/");
            $files = $dir->getDirectoryContents('file');
            foreach ($files as $file) {
                if (strstr($file->getName(), 'item_'.$entry['id'].'.')) {
                    $file->delete();
                }
            }
        }
        $ok = $this->getListtoolDAO()->deleteItemByCategory($oid);
        return true;
    }
}
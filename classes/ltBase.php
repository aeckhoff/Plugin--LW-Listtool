<?php

/**************************************************************************
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
***************************************************************************/

class ltBase  {
    
	public function __construct() {
        $this->setRepository();
        $this->request  = lw_registry::getInstance()->getEntry("request");
        $this->response = lw_registry::getInstance()->getEntry("response");
        $this->in_auth  = lw_in_auth::getInstance();
        $this->auth     = lw_registry::getInstance()->getEntry("auth");
        $this->config   = lw_registry::getInstance()->getEntry("config");
        $this->db       = lw_registry::getInstance()->getEntry("db");
        $this->pid      = lw_page::getInstance()->getPageValue("id");
    }
    
    public function deactivate($array) 
    {
        $this->deactivated = $array;
    }
    
    public function isDeactivated($function)
    {
        return in_array($function, $this->deactivated);
    }    
    
    public function setEntryId($id) {
        $this->eid = intval($id);
    }
    
    public function getEntryId() {
        return $this->eid;
    }
    
    public function setCommandAndIdIdentifier($cmd, $id) {
        $this->cmdIdentifier = $cmd;
        $this->idIdentifier = $id;
    }
    
    public function setOid($oid) {
        $this->oid = $oid;
    }
    
    public function getOid() {
        return $this->oid;
    }    

    public function setPlugindata($data) {
        $this->plugindata = $data;
    }
    
    public function getPluginParameter($key) {
        return $this->plugindata['parameter'][$key];
    }
    
    public function getPluginParameterByCid($cid, $key) {
        $data = lw_registry::getInstance()->getEntry("repository")->plugins()->loadPluginData('lw_listtool', $cid);
        return $data['parameter'][$key];
    }
    
    public function setRepository($repository=false) {
        if ($repository) {
            $this->repository = $repository;
        } else {
            include_once(dirname(__FILE__).'/ltRepository.php');
            $this->repository = new ltRepository();
        }
    }
    
    public function buildPopup($output) {
        $tpl = new lw_te(lw_io::loadFile(dirname(__FILE__).'/../templates/popup.tpl.html'));
        
        $tpl->reg('form',   $output);
        $tpl->reg('jquery', $this->response->getJQueryIncludes());
                
        die($tpl->parse());
    }
    
    public function reloadParent($array) {
        die(lw_io::loadFile(dirname(__FILE__).'/../templates/reloadparent.tpl.html'));
    }    
    
    public function getOutput() {
        return $this->output;
    }
    
    public function isReadAllowedByItemId($id) {
        $pid = $this->repository->getPageIdByItemId($id);
        $cid = $this->repository->getContainerIdByItemId($id);
        $authOK     = $this->auth->isInPages($pid);
        $inauthOK   = $this->in_auth->isObjectAllowed('page', $pid);
        if ($authOK || $inauthOK) return true;
        return false;
    }
    
    public function isReadAllowed() {
        $authOK     = $this->auth->isInPages($this->pid);
        $inauthOK   = $this->in_auth->isObjectAllowed('page', $this->pid);
        if ($authOK || $inauthOK) return true;
        return false;
    }
    
    public function isWriteAllowedByItemId($id) {
        $cid = $this->repository->getContainerIdByItemId($id);
        $inauthOK   = $this->in_auth->isObjectAllowed('listtool_cbox', $cid);
        if ($inauthOK) return true;
        return false;
    }    
    
    public function isWriteAllowed() {
        $inauthOK   = $this->in_auth->isObjectAllowed('listtool_cbox', $this->getOid());
        if ($inauthOK) return true;
        return false;
    }
}
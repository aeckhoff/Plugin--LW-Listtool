<?php

/* * ************************************************************************
 *  Copyright notice
 *
 *  Copyright 2011-2012 Logic Works GmbH
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

// needs update in lw_file
// needs update in index.php/admin.php/preview.php

class lw_listtool extends lw_pluginbase
{
    public function __construct()
    {
        $this->className = "lw_listtool";
        parent::__construct();
        $this->maxFileSize = array();
        include_once(dirname(__FILE__) . '/classes/ltBase.php');
        $this->deactivate = array('mailMessage');
    }

    public function _loadPluginData()
    {
        return lw_registry::getInstance()->getEntry("repository")->plugins()->loadPluginData('lw_listtool', $this->getOid());
    }

    public function reload($cmd)
    {
        $this->pageReload($this->buildCommandURL($cmd));
    }

    public function wf_showError()
    {
        die("an error occured [lw_listtool]");
    }

    public function isOutput()
    {
        if (strlen(trim($this->output)) > 0)
            return true;
    }

    public function buildCommandURL($cmd, $id=false, $array=false)
    {
        $array[$this->idIdentifier] = $id;
        $array[$this->cmdIdentifier] = $cmd;
        return lw_page::getInstance()->getUrl($array);
    }

    public function deleteEntry()
    {
        $this->_ListFactory();
        return $this->list->delete();
    }

    public function getOutput()
    {
        if ($this->params['oid'] > 0) {
            $this->setOid($this->params['oid']);
        }
        $this->_backendFactory();
        $this->backend->execute();
        return $this->backend->getOutput();
    }

    public function buildPageOutput()
    {
        if ($this->params['oid'] > 0) {
            $this->setOid($this->params['oid']);
        }
        $this->setCommandAndIdIdentifier();
        $this->wf = new lw_wfengine($this->className);
        $this->wf->setWFObject($this);
        $this->wf->setDebug(false);
        $this->wf->setDefaultCommand('showList');
        $this->wf->executeWF($this->request->getAlnum($this->cmdIdentifier));
        return $this->output;
    }

    public function setCommandAndIdIdentifier($cmd=false, $id=false)
    {
        $this->cmdIdentifier = "lwlt_" . $this->getOid() . "cmd";
        $this->idIdentifier = "lwlt_" . $this->getOid() . "id";
    }

    public function wf_showList()
    {
        $this->_ListFactory();
        $this->list->getList();
        $this->output = $this->list->getOutput();
        if (!$this->isOutput())
            die("error");
    }

    public function wf_showSortList()
    {
        $this->_ListFactory();
        $this->list->getSortList();
        $this->output = $this->list->getOutput();
        if (!$this->isOutput()) {
            $this->wf->setMustReload(true);
            return $return;
        }
    }

    public function wf_download()
    {
        $this->_ListFactory();
        $this->list->getDownload();
        exit();
    }

    public function wf_editEntry()
    {
        $this->_CRUDFactory();
        $return = $this->crud->editEntry();
        $this->output = $this->crud->getOutput();
        if (!$this->isOutput()) {
            $this->wf->setMustReload(true);
            return $return;
        }
    }

    public function wf_deleteEntry()
    {
        $this->_CRUDFactory();
        $return = $this->crud->deleteEntry();
        $this->output = $this->crud->getOutput();
        if (!$this->isOutput()) {
            $this->wf->setMustReload(true);
            return $return;
        }
    }

    public function wf_addEntry()
    {
        $this->_CRUDFactory();
        $return = $this->crud->addEntry();
        $this->output = $this->crud->getOutput();
        if (!$this->isOutput()) {
            $this->wf->setMustReload(true);
            return $return;
        }
    }

    public function wf_showThumbnail()
    {
        $this->_ListFactory();
        $this->list->getThumbnail();
        exit();
    }

    private function _ListFactory()
    {
        if (!$this->list) {
            include_once(dirname(__FILE__) . '/classes/ltList.php');
            $this->list = new ltList();
            $this->list->setOid($this->getOid());
            $this->list->setPlugindata($this->_loadPluginData());
            $this->list->setCommandAndIdIdentifier($this->cmdIdentifier, $this->idIdentifier);
            $this->list->setEntryId($this->request->getInt($this->idIdentifier));
        }
    }

    private function _CRUDFactory()
    {
        if (!$this->crud) {
            include_once(dirname(__FILE__) . '/classes/ltCRUD.php');
            $this->crud = new ltCRUD();
            $this->crud->setOid($this->getOid());
            $this->crud->setPlugindata($this->_loadPluginData());
            $this->crud->setCommandAndIdIdentifier($this->cmdIdentifier, $this->idIdentifier);
            $this->crud->setEntryId($this->request->getInt($this->idIdentifier));
            $this->crud->deactivate($this->deactivate);
        }
    }

    private function _backendFactory()
    {
        if (!$this->backend) {
            include_once(dirname(__FILE__) . '/classes/ltBackend.php');
            $this->backend = new ltBackend();
            $this->backend->setOid($this->getOid());
            $this->backend->deactivate($this->deactivate);
        }
    }
}
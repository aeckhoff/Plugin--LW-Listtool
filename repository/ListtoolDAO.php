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

/*
 * opt1bool = 1:link; 0:file
 * opt1text = info
 * opt2text = keywords
 * opt3text = linkurl / filename
 * opt4text = thumbnail
 * opt1number = sequence
 * opt2number = freedate
 * opt3number = lastupload
 */

class ListtoolDAO {

    public function __construct(lw_db $db, $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    private function _getBaseWhere($options=array())
    {
        $where = " m.lw_object = 'lw_listtool' ";
        if ($options['oid'] > 0) {
            $where.= 'AND m.category_id = '.$options['oid'].' ';
        }
        if ($options['published'] == true) {
            $where.= 'AND m.published = 1 ';
        }
        if ($options['disabled'] != true) {
            $where.= 'AND (m.disabled IS NULL OR m.disabled < 1) ';
        } else {
            $where.= 'AND m.disabled = 1 ';
        }
        return $where;
    }

    public function loadListByOid($oid, $sorting, $onlypublished=true)
    {
        $this->db->setStatement("SELECT DISTINCT m.name, m.id, m.opt1number, m.lw_first_date, m.published, m.opt3text, m.opt1bool, u.name as last_username FROM t:lw_master m LEFT JOIN t:lw_in_user u ON m.lw_last_user = u.id  WHERE ".$this->_getBaseWhere(array('oid' => $oid, 'published' => $onlypublished))." AND m.id > 0 ORDER BY m.".$sorting);
        return $this->db->pselect($sql);
    }

    public function loadEntryById($id)
    {
        $this->db->setStatement("SELECT m.*, u.name as last_username FROM t:lw_master m LEFT JOIN t:lw_in_user u ON m.lw_last_user = u.id WHERE ".$this->_getBaseWhere()." AND m.id = ".$id);
        return $this->db->pselect1();
    }

    public function getPageIdByItemId($id)
    {
        $this->db->setStatement("SELECT page_id FROM t:lw_container WHERE id = ".$this->getContainerIdByItemId($id));
        $erg = $this->db->pselect1();
        return $erg['page_id'];
    }

    public function getContainerIdByItemId($id)
    {
        $data = $this->loadEntryById($id);
        return $data['category_id'];
    }

    public function deleteEntryById($id)
    {
        return $this->db->lwDeleteEntry($this->db->gt("lw_master"), $id);
    }

    public function getHighestSeqInPage($oid)
    {
        $this->db->setStatement("SELECT max(opt1number) as maxseq FROM t:lw_master m WHERE ".$this->_getBaseWhere(array('oid' => $oid)));
        $erg = $this->db->pselect1($sql);
        return $erg['maxseq'];
    }

    private function prepareArray($array) {
        if (is_array($array)) {
            foreach($array as $key => $value) {
                $newarray[$key] = $this->db->quote($value);
            }
            return $newarray;
        }
        return array();
    }
    
    public function insertEntry($array) {
        $array = $this->prepareArray($array);
        return $this->db->lwInsertEntry($this->db->gt("lw_master"), $array);
    }
    
    public function updateEntry($array, $id) {
        $array = $this->prepareArray($array);
        return $this->db->lwUpdateEntry($this->db->gt("lw_master"), $array, $id);
    }
    
    public function saveFile($tmp, $name, $archive=false)
    {
        $dir = lw_directory::getInstance($this->config['path']['listtool']);
        if ($dir->fileExists($name)) {
            if ($archive) {
                $target = $dir->getPath().'archive/'.$name.'_'.date(YmdHis);
                copy($dir->getPath().$name, $target);
                $this->_updatePermissions($target);
            }
            $ok = $dir->deleteFile($name);
        }
        $ok = $dir->addFile($tmp, $name);
        $this->_updatePermissions($dir->getPath().$name);
    }
    
    private function _updatePermissions($file)
    {
        if ($this->config['files']['chgrp']) {
            @chgrp($file, $this->config['files']['chgrp']);
        }
        if ($this->config['files']['chmod']) {
            @chmod($file, octdec($this->config['files']['chmod']));
        }
    }

    public function saveSequence($id, $seq)
    {
        $this->db->setStatement("UPDATE t:lw_master SET opt1number = :seq WHERE id = :id");
        $this->db->bindParameter('id', 'i', $id);
        $this->db->bindParameter('seq', 'i', $seq);
        return $this->db->pdbquery();
    }

    public function deleteItemByCategory($oid) {
        $sql = "DELETE FROM ".$this->db->gt("lw_master")." WHERE category_id = ".intval($oid);
        return $this->db->dbquery($sql);
    }
}

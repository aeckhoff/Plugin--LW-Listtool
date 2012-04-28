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

class PluginDAO {

    public function __construct(lw_db $db)
    {
        $this->db = $db;
    }

    public function deleteEntryByContainer($cid)
    {
        $this->db->setStatement("DELETE FROM t:lw_plugins WHERE container_id = :cid");
        $this->db->bindParameter('cid', 'i', $cid);
        return $this->db->pdbquery();
    }

    public function loadPluginData($plugin, $cid)
    {
        $this->db->setStatement("SELECT * FROM t:lw_plugins WHERE plugin = :plugin AND container_id = :cid");
        $this->db->bindParameter('plugin', 's', $plugin);
        $this->db->bindParameter('cid', 'i', $cid);
        $erg = $this->db->pselect1();
        if (!$erg['id']) {
            $this->db->setStatement("INSERT INTO t:lw_plugins (plugin, container_id) VALUES (:plugin, :cid)");
            $this->db->bindParameter('plugin', 's', $plugin);
            $this->db->bindParameter('cid', 'i', $cid);
            $ok = $this->db->pdbquery();
        }
        if ($erg['parameter']) {
            $data['parameter'] = unserialize(stripslashes($erg['parameter']));
        } else {
            $data['parameter'] = array();
        }
        $data['content'] = stripslashes($erg['content']);
        $data['item_id'] = intval($erg['item_id']);
        return $data;
    }

    public function savePluginData($plugin, $cid, $parameter=false, $content=false, $item_id=false)
    {
        $this->db->setStatement("UPDATE t:lw_plugins set parameter = :parameter, content = :content, item_id = :item_id WHERE plugin = :plugin AND container_id = :cid");
        $this->db->bindParameter('parameter', 's', addslashes(serialize($parameter)));
        $this->db->bindParameter('content', 's', addslashes($content));
        $this->db->bindParameter('item_id', 'i', $item_id);
        $this->db->bindParameter('plugin', 's', $plugin);
        $this->db->bindParameter('cid', 'i', $cid);
        $ok = $this->db->pdbquery();
        return $ok;
    }

}

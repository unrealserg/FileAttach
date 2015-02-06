<?php
/**
 * FileAttach
 *
 * Copyright 2015 by Vitaly Checkryzhev <13hakta@gmail.com>
 *
 * This file is part of FileAttach, tool to attach files to resources with
 * MODX Revolution's Manager.
 *
 * FileAttach is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation version 3,
 *
 * FileAttach is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * FileAttach; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package FileAttach
*/

class FileItem extends xPDOSimpleObject {
    public $source = false;
    public $files_path = '';

    private function getMediaSource() {
        if ($this->source) return $this->source;
        //get modMediaSource
        $mediaSource = $this->xpdo->getOption('fileattach.mediasource', null, 1);

        $def = $this->xpdo->getObject('sources.modMediaSource', array('id' => $mediaSource));
        $def->initialize();
        $this->source = $def;

	$this->files_path = $this->xpdo->getOption('fileattach.files_path');

        return $this->source;
    }

    public function getUrl() {
        $ms = $this->getMediaSource();
	return $ms->getBaseUrl() . $this->getPath();
    }

    public function getPath() {
	return $this->files_path . $this->get('path') . $this->get('internal_name');
    }

    public function getFullPath() {
        $ms = $this->getMediaSource();
	return $ms->getBasePath() . $this->getPath();
    }

    public function getSize() {
     $ms = $this->getMediaSource();
     $f = $ms->fileHandler->make($this->getFullPath(), array(), 'modFile');
     return $f->getSize();
    }

    public function rename($newname, $forceSave = true) {
	$local_path = $this->files_path . $this->get('path');

        $ms = $this->getMediaSource();

        if ($ms->renameObject($local_path . $this->get('internal_name'), $newname)) {
	 $this->set('name', $newname);
	 $this->set('internal_name', $newname);

	 if ($forceSave) $this->save();
	} else {
	 return false;
	}

	return true;
    }

    public function setPrivate($state) {
	if ($this->get('private') == $state) return true;

        $ms = $this->getMediaSource();

	$local_path = $this->files_path . $this->get('path');
	$path = $ms->getBasePath() . $local_path;

	// Generate name and check for existence
	if ($state)
	 $filename = $this->generateName();
	else
	 $filename = $this->get('name');

	$fullpath = '';

	// Check intersection with existing
	while(1) {
	 $f = $ms->fileHandler->make($path . '/' . $filename, array(), 'modFile');
	 if (!$f->exists()) break;

	 if ($state)
	  $filename = $this->generateName();
	 else
	  $filename = $this->generateName(4) . '_' . $filename;
        }

        if ($ms->renameObject(
	    $local_path . $this->get('internal_name'),
	    $filename)) {
	 $this->set('internal_name', $filename);
	 $this->set('private', $state);
	 $this->save();

	 return true;
	} else {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR,'[FileAttach] An error occurred while trying to rename the attachment file at: ' . $filename);
	}

	return false;
    }

    public function remove(array $ancestors= array ()) {
	$filename = $this->getPath();
        if (!empty($filename)) {
            $ms = $this->getMediaSource();
            if (!@$ms->removeObject($filename)) {
                $this->xpdo->log(xPDO::LOG_LEVEL_ERROR,'[FileAttach] An error occurred while trying to remove the attachment file at: ' . $filename);
            }
        }

        return parent::remove($ancestors);
    }

    /* Generate Filename
     *
     * @param   integer  $length        Length of generated sequence
     * @return  string
     */
    public function generateName($length = 32) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_';
        $charactersLength = strlen($characters);

	$newname = '';

	for ($i = 0; $i < $length; $i++)
         $newname .= $characters[rand(0, $charactersLength - 1)];

	$newname .= '.txt';

	return $newname;
    }

    /* Sanitize Filename
     *
     * @param   string  $str        Input file name
     * @return  string
     */
    public function sanitizeName($str) {
	$bad = array(
        '../', '<!--', '-->', '<', '>',
        "'", '"', '&', '$', '#',
        '{', '}', '[', ']', '=',
        ';', '?', '%20', '%22',
        '%3c',      // <
        '%253c',    // <
        '%3e',      // >
        '%0e',      // >
        '%28',      // (
        '%29',      // )
        '%2528',    // (
        '%26',      // &
        '%24',      // $
        '%3f',      // ?
        '%3b',      // ;
        '%3d',       // =
	'/', './', '\\'
	);

	return stripslashes(str_replace($bad, '', $str));
    }
}
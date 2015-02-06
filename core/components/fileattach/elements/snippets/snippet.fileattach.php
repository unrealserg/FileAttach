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

/** @var array $scriptProperties */
/** @var FileAttach $FileAttach */
if (!$FileAttach = $modx->getService('fileattach', 'FileAttach', $modx->getOption('fileattach.core_path', null, $modx->getOption('core_path') . 'components/fileattach/') . 'model/fileattach/', $scriptProperties)) {
    return 'Could not load FileAttach class!';
}

// Do your snippet code here.
$tpl = $modx->getOption('tpl', $scriptProperties, 'FileItemTpl');
$sortby = $modx->getOption('sortby', $scriptProperties, 'name');
$sortdir = $modx->getOption('sortbir', $scriptProperties, 'ASC');
$limit = $modx->getOption('limit', $scriptProperties, 0);
$outputSeparator = $modx->getOption('outputSeparator', $scriptProperties, "\n");
$toPlaceholder = $modx->getOption('toPlaceholder', $scriptProperties, false);
$showHASH = $modx->getOption('showHASH', $scriptProperties, false);
$resource = $modx->getOption('resource', $scriptProperties, 0);
$makeUrl = $modx->getOption('makeUrl', $scriptProperties, true);
$privateUrl = $modx->getOption('privateUrl', $scriptProperties, false);
$showSize = $modx->getOption('showSize', $scriptProperties, false);

if ($makeUrl) {
 if (!$privateUrl || $showSize) {
  // Get base URLs
  $mediaSource = $this->xpdo->getOption('fileattach.mediasource',null,1);

  $ms = $this->xpdo->getObject('sources.modMediaSource', array('id' => $mediaSource));
  $ms->initialize();

  $files_path = $modx->getOption('fileattach.files_path');
  $public_url = $ms->getBaseUrl() . $files_path;
  $docs_path  = $ms->getBasePath() . $files_path;
 }

 $private_url = $modx->getOption('fileattach.assets_url', null, $modx->getOption('assets_url')) . 'components/fileattach/';
 $private_url .= 'connector.php?action=web/download&ctx=web&id=';
}

// Build query
$c = $modx->newQuery('FileItem');
$c->sortby($sortby, $sortdir);
$c->limit($limit);

if ($showHASH)
 $c->select('hash');

$c->where(array('docid' => ($resource > 0)? $resource : $modx->resource->get('id')));

$items = $modx->getIterator('FileItem', $c);

// Iterate through items
$list = array();
/** @var FileItem $item */
foreach ($items as $item) {
 $item->source = $ms;
 $item->files_path = $files_path;

 $itemArr = $item->toArray();

 if ($makeUrl) {
  if ($item->get('private') || $privateUrl)
   $itemArr['url'] = $private_url . $item->get('id');
    else
   $itemArr['url'] = $public_url . $item->get('path') . $item->get('name');
 }

 if ($showSize)
  $itemArr['size'] = $item->getSize();

 $list[] = $modx->getChunk($tpl, $itemArr);
}

// Output
$output = implode($outputSeparator, $list);
if (!empty($toPlaceholder)) {
 // If using a placeholder, output nothing and set output to specified placeholder
 $modx->setPlaceholder($toPlaceholder, $output);

 return '';
}

return $output;
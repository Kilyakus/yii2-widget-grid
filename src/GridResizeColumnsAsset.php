<?php
namespace kilyakus\widget\grid;

use \kilyakus\widgets\AssetBundle;

class GridResizeColumnsAsset extends AssetBundle
{
    public function init()
    {
        $this->depends = array_merge($this->depends, ['kilyakus\widget\grid\GridViewAsset']);
        $this->setSourcePath(__DIR__ . '/assets');
        $this->setupAssets('js', ['js/jquery.resizableColumns']);
        $this->setupAssets('css', ['css/jquery.resizableColumns']);
        parent::init();
    }
}

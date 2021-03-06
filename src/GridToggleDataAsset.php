<?php
namespace kilyakus\widget\grid;

use kilyakus\widgets\AssetBundle;

class GridToggleDataAsset extends AssetBundle
{
    public function init()
    {
        $this->depends = array_merge(["kilyakus\\widget\\grid\\GridViewAsset"], $this->depends);
        $this->setSourcePath(__DIR__ . '/assets');
        $this->setupAssets('js', ['js/kv-grid-toggle']);
        parent::init();
    }
}

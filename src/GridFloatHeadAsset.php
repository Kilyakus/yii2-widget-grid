<?php
namespace kilyakus\widget\grid;

use kilyakus\widgets\AssetBundle;

class GridFloatHeadAsset extends AssetBundle
{
    public function init()
    {
        $this->depends = array_merge(["kilyakus\\widget\\grid\\GridViewAsset"], $this->depends);
        $this->setSourcePath(__DIR__ . '/assets');
        $this->setupAssets('js', ['js/jquery.floatThead']);
        parent::init();
    }
}

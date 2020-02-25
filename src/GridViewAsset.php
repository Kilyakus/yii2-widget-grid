<?php
namespace kilyakus\widget\grid;

use kilyakus\widgets\AssetBundle;

class GridViewAsset extends AssetBundle
{
    public function init()
    {
        $this->depends = array_merge(["kartik\\dialog\\DialogAsset", "yii\\grid\\GridViewAsset"], $this->depends);
        $this->setSourcePath(__DIR__ . '/assets');
        $this->setupAssets('css', ['css/kv-grid']);
        parent::init();
    }
}

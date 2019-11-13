<?php

/**
 * @package   yii2-grid
 * @author    Kartik Visweswaran <kartikv2@gmail.com>
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2014 - 2019
 * @version   3.3.4
 */

namespace kilyakus\widget\grid;

use \kilyakus\widgets\AssetBundle;

/**
 * Asset bundle for resizable columns functionality for the [[GridView]] widget.
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class GridResizeColumnsAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->depends = array_merge($this->depends, ['kilyakus\widget\grid\GridViewAsset']);
        $this->setSourcePath(__DIR__ . '/assets');
        $this->setupAssets('js', ['js/jquery.resizableColumns']);
        $this->setupAssets('css', ['css/jquery.resizableColumns']);
        parent::init();
    }
}
